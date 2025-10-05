<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\MenuCategory;

class MenuCategoryController extends Controller
{
    // роли: owner/manager — можно изменять; waiter — только смотреть
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

    // GET /restaurants/{restaurant}/menu-categories
    public function index(Request $r, Restaurant $restaurant)
    {
        $this->authView($r->user(), $restaurant);
        return $restaurant->categories()->orderBy('priority')->get();
    }

    // POST /restaurants/{restaurant}/menu-categories
    public function store(Request $r, Restaurant $restaurant)
    {
        $this->authManage($r->user(), $restaurant);
        $data = $r->validate([
            'name' => 'required|string|max:200',
            'priority' => 'integer|min:0'
        ]);
        $data['restaurant_id'] = $restaurant->id;
        $cat = MenuCategory::create($data);
        return response()->json($cat, 201);
    }

    // GET /restaurants/{restaurant}/menu-categories/{category}
    public function show(Request $r, Restaurant $restaurant, MenuCategory $category)
    {
        $this->authView($r->user(), $restaurant);
        abort_if($category->restaurant_id !== $restaurant->id, 404);
        return $category->load('items');
    }

    // PUT /restaurants/{restaurant}/menu-categories/{category}
    public function update(Request $r, Restaurant $restaurant, MenuCategory $category)
    {
        $this->authManage($r->user(), $restaurant);
        abort_if($category->restaurant_id !== $restaurant->id, 404);
        $data = $r->validate([
            'name' => 'sometimes|required|string|max:200',
            'priority' => 'sometimes|integer|min:0'
        ]);
        $category->update($data);
        return $category;
    }

    // DELETE /restaurants/{restaurant}/menu-categories/{category}
    public function destroy(Request $r, Restaurant $restaurant, MenuCategory $category)
    {
        $this->authManage($r->user(), $restaurant);
        abort_if($category->restaurant_id !== $restaurant->id, 404);
        $category->delete();
        return response()->noContent();
    }
}
