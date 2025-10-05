<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\RestaurantWorkSchedule;
use App\Models\RestaurantWorkException;

class WorkScheduleController extends Controller
{
    public function index(Request $r, Restaurant $restaurant) {
        $this->authView($r->user(), $restaurant);
        return [
            'week'=>$restaurant->schedules()->orderBy('weekday')->get(),
            'exceptions'=>$restaurant->scheduleExceptions()->orderBy('date','desc')->take(30)->get(),
        ];
    }
    public function upsertWeek(Request $r, Restaurant $restaurant) {
        $this->authManage($r->user(), $restaurant);
        $items = $r->validate([
            'items'=>'required|array|min:1',
            'items.*.weekday'=>'required|integer|min:0|max:6',
            'items.*.is_closed'=>'required|boolean',
            'items.*.open_at'=>'nullable|date_format:H:i',
            'items.*.close_at'=>'nullable|date_format:H:i',
        ])['items'];
        foreach ($items as $it) {
            RestaurantWorkSchedule::updateOrCreate(
                ['restaurant_id'=>$restaurant->id,'weekday'=>$it['weekday']],
                ['is_closed'=>$it['is_closed'],'open_at'=>$it.get('open_at'), 'close_at'=>$it.get('close_at')]
            );
        }
        return ['ok'=>true];
    }
    public function exceptions(Request $r, Restaurant $restaurant) {
        $this->authView($r->user(), $restaurant);
        return $restaurant->scheduleExceptions()->orderBy('date','desc')->paginate(50);
    }
    public function storeException(Request $r, Restaurant $restaurant) {
        $this->authManage($r->user(), $restaurant);
        $data = $r->validate([
            'date'=>'required|date',
            'is_closed'=>'required|boolean',
            'open_at'=>'nullable|date_format:H:i',
            'close_at'=>'nullable|date_format:H:i',
        ]);
        $ex = RestaurantWorkException::updateOrCreate(
            ['restaurant_id'=>$restaurant->id,'date'=>$data['date']],
            ['is_closed'=>$data['is_closed'],'open_at'=>$data.get('open_at'), 'close_at'=>$data.get('close_at')]
        );
        return response()->json($ex, 201);
    }
    public function deleteException(Request $r, Restaurant $restaurant, $id) {
        $this->authManage($r->user(), $restaurant);
        $restaurant->scheduleExceptions()->where('id',$id)->delete();
        return response()->noContent();
    }

    protected function role($user, Restaurant $r) {
        return $r->users()->where('users.id',$user->id)->first()?->pivot?->role;
    }
    protected function authView($u, Restaurant $r){ abort_if(!$this->role($u,$r),403); }
    protected function authManage($u, Restaurant $r){ abort_if(!in_array($this->role($u,$r),['owner','manager']),403); }
}
