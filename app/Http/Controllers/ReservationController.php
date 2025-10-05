<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Restaurant;
use App\Models\Reservation;
use App\Models\Table;

class ReservationController extends Controller
{
    // --- базовые проверки роли (owner/manager могут изменять, waiter — читать) ---
    protected function role($user, Restaurant $r)
    {
        return $r->users()->where('users.id', $user->id)->first()?->pivot?->role;
    }

    protected function authView($u, Restaurant $r)
    {
        abort_if(!$this->role($u, $r), 403);
    }

    protected function authManage($u, Restaurant $r)
    {
        abort_if(!in_array($this->role($u, $r), ['owner', 'manager']), 403);
    }

    // --- Доступность: GET /restaurants/{restaurant}/availability ---
    public function availability(Request $r, Restaurant $restaurant)
    {
        $this->authView($r->user(), $restaurant);

        $data = $r->validate([
            'datetime_local' => 'required|date_format:Y-m-d\TH:i',
            'duration_min' => 'integer|min:15|max:360',
            'guests' => 'required|integer|min:1',
            'zone_ids' => 'array',
            'zone_ids.*' => 'integer'
        ]);

        $duration = $data['duration_min'] ?? 90;
        $start = new \DateTime($data['datetime_local']);
        $end = (clone $start)->modify("+{$duration} minutes");

        // кандидаты по вместимости/активности/зонам
        $tablesQ = Table::query()
            ->whereHas('zone.hall', fn($q) => $q->where('restaurant_id', $restaurant->id))
            ->where('is_active', true)
            ->where('seats', '>=', $data['guests']);

        if (!empty($data['zone_ids'])) $tablesQ->whereIn('zone_id', $data['zone_ids']);

        $tables = $tablesQ->get(['id', 'zone_id', 'code', 'seats']);
        if ($tables->isEmpty()) {
            return ['exact' => [], 'alternatives' => [], 'explanations' => ['Нет подходящих столов по вместимости.']];
        }

        // конфликты по пересечению интервала и пересечению наборов столов
        $tableIds = $tables->pluck('id')->all();
        $conflicting = Reservation::active()
            ->where('restaurant_id', $restaurant->id)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)->where('end_at', '>', $start);
            })
            ->whereHas('tables', fn($q) => $q->whereIn('tables.id', $tableIds))
            ->with('tables:id')
            ->get();

        $busyIds = $conflicting->flatMap(fn($res) => $res->tables->pluck('id'))->unique()->values()->all();
        $free = $tables->whereNotIn('id', $busyIds)->values()->all();

        $exact = [];
        foreach ($free as $t) {
            $exact[] = [
                'datetime_local' => $start->format('Y-m-d\TH:i'),
                'table_ids' => [$t->id],
                'score' => 1.0,
                'zone_id' => $t->zone_id,
            ];
        }

        $alternatives = [];
        if (empty($exact)) {
            foreach ([-60, -30, 30, 60] as $delta) {
                $alternatives[] = [
                    'datetime_local' => (clone $start)->modify(($delta >= 0 ? '+' : '') . $delta . ' minutes')->format('Y-m-d\TH:i'),
                    'table_ids' => [],
                    'score' => 0.5
                ];
            }
        }

        return ['exact' => $exact, 'alternatives' => $alternatives, 'explanations' => []];
    }

    // --- Список броней ---
    public function index(Request $r, Restaurant $restaurant)
    {
        $this->authView($r->user(), $restaurant);

        $q = Reservation::where('restaurant_id', $restaurant->id)->orderBy('start_at', 'desc');

        if ($r->filled('date')) $q->whereDate('start_at', $r->get('date'));
        if ($r->filled('status')) $q->where('status', $r->get('status'));

        return $q->with('tables')->paginate(50);
    }

    // --- Создать бронь (поддерживает Idempotency-Key в заголовке) ---
    public function store(Request $r, Restaurant $restaurant)
    {
        $this->authManage($r->user(), $restaurant);

        $data = $r->validate([
            'guest_name' => 'required|string',
            'guest_phone' => 'required|string',
            'datetime_local' => 'required|date_format:Y-m-d\TH:i',
            'duration_min' => 'integer|min:15|max:360',
            'guests' => 'required|integer|min:1',
            'table_ids' => 'required|array|min:1',
            'table_ids.*' => 'integer|exists:tables,id',
            'notes' => 'nullable|string'
        ]);

        $duration = $data['duration_min'] ?? 90;
        $start = new \DateTime($data['datetime_local']);
        $end = (clone $start)->modify("+{$duration} minutes");

        // идемпотентность
        $idem = $r->header('Idempotency-Key');
        if ($idem) {
            $existing = Reservation::where('idempotency_key', $idem)->first();
            if ($existing) return response()->json($existing->load('tables'), 200);
        }

        // проверка, что столы принадлежат ресторану
        $tables = Table::whereIn('id', $data['table_ids'])
            ->whereHas('zone.hall', fn($q) => $q->where('restaurant_id', $restaurant->id))
            ->get();

        abort_if($tables->count() !== count($data['table_ids']), 422, 'Некорректный набор столов');

        // проверка пересечений
        $conflict = Reservation::active()
            ->where('restaurant_id', $restaurant->id)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)->where('end_at', '>', $start);
            })
            ->whereHas('tables', fn($q) => $q->whereIn('tables.id', $tables->pluck('id')->all()))
            ->exists();

        abort_if($conflict, 409, 'Стол(ы) заняты на это время');

        $res = DB::transaction(function () use ($r, $restaurant, $data, $start, $end, $tables, $idem) {
            $res = Reservation::create([
                'restaurant_id' => $restaurant->id,
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'],
                'start_at' => $start,
                'end_at' => $end,
                'guests' => $data['guests'],
                'status' => 'confirmed',
                'notes' => $data['notes'] ?? null,
                'source' => 'api',
                'created_by' => $r->user()->id,
                'idempotency_key' => $idem
            ]);
            $res->tables()->sync($tables->pluck('id')->all());
            return $res->load('tables');
        });

        return response()->json($res, 201);
    }

    // --- Просмотр ---
    public function show(Request $r, Restaurant $restaurant, Reservation $reservation)
    {
        $this->authView($r->user(), $restaurant);
        abort_if($reservation->restaurant_id !== $restaurant->id, 404);
        return $reservation->load('tables');
    }

    // --- Обновление (смена времени/столов/статуса) ---
    public function update(Request $r, Restaurant $restaurant, Reservation $reservation)
    {
        $this->authManage($r->user(), $restaurant);
        abort_if($reservation->restaurant_id !== $restaurant->id, 404);

        $data = $r->validate([
            'datetime_local' => 'nullable|date_format:Y-m-d\TH:i',
            'duration_min' => 'nullable|integer|min:15|max:360',
            'guests' => 'nullable|integer|min:1',
            'table_ids' => 'nullable|array|min:1',
            'table_ids.*' => 'integer|exists:tables,id',
            'status' => 'nullable|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string'
        ]);

        $start = $reservation->start_at;
        $end = $reservation->end_at;

        if (!empty($data['datetime_local'])) {
            $start = new \DateTime($data['datetime_local']);
        }
        if (array_key_exists('duration_min', $data)) {
            $dur = $data['duration_min'] ?? 90;
            $end = (clone $start)->modify("+{$dur} minutes");
        } elseif ($start != $reservation->start_at) {
            $mins = $reservation->end_at->diffInMinutes($reservation->start_at);
            $end = (clone $start)->modify("+{$mins} minutes");
        }

        $tableIds = $data['table_ids'] ?? $reservation->tables()->pluck('tables.id')->all();

        // валидация принадлежности столов ресторану
        $tables = Table::whereIn('id', $tableIds)
            ->whereHas('zone.hall', fn($q) => $q->where('restaurant_id', $restaurant->id))
            ->get();
        abort_if($tables->count() !== count($tableIds), 422, 'Некорректный набор столов');

        // конфликты, исключая текущую бронь
        $conflict = Reservation::active()
            ->where('restaurant_id', $restaurant->id)
            ->where('id', '!=', $reservation->id)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)->where('end_at', '>', $start);
            })
            ->whereHas('tables', fn($q) => $q->whereIn('tables.id', $tables->pluck('id')->all()))
            ->exists();
        abort_if($conflict, 409, 'Стол(ы) заняты на это время');

        $reservation->start_at = $start;
        $reservation->end_at = $end;
        if (isset($data['guests'])) $reservation->guests = $data['guests'];
        if (isset($data['status'])) $reservation->status = $data['status'];
        if (isset($data['notes'])) $reservation->notes = $data['notes'];
        $reservation->save();

        $reservation->tables()->sync($tables->pluck('id')->all());

        return $reservation->load('tables');
    }

    // --- Отмена (soft) ---
    public function destroy(Request $r, Restaurant $restaurant, Reservation $reservation)
    {
        $this->authManage($r->user(), $restaurant);
        abort_if($reservation->restaurant_id !== $restaurant->id, 404);
        $reservation->status = 'cancelled';
        $reservation->save();
        return response()->noContent();
    }
}
