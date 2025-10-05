<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Hall;

class HallController extends Controller
{
    public function index(Request $r, Restaurant $restaurant)
    {
        $this->authView($r->user(), $restaurant);
        return $restaurant->halls()->orderBy('priority')->get();
    }

    public function store(Request $r, Restaurant $restaurant)
    {
        $this->authManage($r->user(), $restaurant);
        $data = $r->validate(['name' => 'required', 'priority' => 'integer']);
        $data['restaurant_id'] = $restaurant->id;
        $h = Hall::create($data);
        return response()->json($h, 201);
    }

    public function show(Request $r, Restaurant $restaurant, Hall $hall)
    {
        $this->authView($r->user(), $restaurant);
        abort_if($hall->restaurant_id !== $restaurant->id, 404);
        return $hall->load('zones.tables');
    }

    public function update(Request $r, Restaurant $restaurant, Hall $hall)
    {
        $this->authManage($r->user(), $restaurant);
        abort_if($hall->restaurant_id !== $restaurant->id, 404);
        $data = $r->validate(['name' => 'sometimes|required', 'priority' => 'integer']);
        $hall->update($data);
        return $hall;
    }

    public function destroy(Request $r, Restaurant $restaurant, Hall $hall)
    {
        $this->authManage($r->user(), $restaurant);
        abort_if($hall->restaurant_id !== $restaurant->id, 404);
        $hall->delete();
        return response()->noContent();
    }

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
}
