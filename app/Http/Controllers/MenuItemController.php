<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\MenuCategory;

class MenuItemController extends Controller
{
    protected function role($user, $r)
    {
        return $r->users()->where('users.id', $user->id)->first()?->pivot?->role;
    }

    protected function authView($u, $r)
    {
        abort_if(!$this->role($u, $r), 403);
    }

    protected function authManage($u, $r)
    {
        abort_if(!in_array($this->role($u, $r), ['owner', 'manager']), 403);
    }

    // GET /menu-categories/{category}/items
    public function index(Request $r, MenuCategory $category)
    {
        $this->authView($r->user(), $category->restaurant);
        $q = $category->items()->orderBy('name');
        if ($r->filled('q')) $q->where('name', 'like', '%' . $r->get('q') . '%');
        return $q->paginate(50);
    }

    // POST /menu-categories/{category}/items
    public function store(Request $r, MenuCategory $category)
    {
        $this->authManage($r->user(), $category->restaurant);
        $data = $r->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'available' => 'boolean',
            'options' => 'array' // модификаторы/варианты (json)
        ]);
        $data['restaurant_id'] = $category->restaurant_id;
        $data['menu_category_id'] = $category->id;
        $item = MenuItem::create($data);
        return response()->json($item, 201);
    }

    // GET /menu-categories/{category}/items/{item}
    public function show(Request $r, MenuCategory $category, MenuItem $item)
    {
        $this->authView($r->user(), $category->restaurant);
        abort_if($item->menu_category_id !== $category->id, 404);
        return $item;
    }

    // PUT /menu-categories/{category}/items/{item}
    public function update(Request $r, MenuCategory $category, MenuItem $item)
    {
        $this->authManage($r->user(), $category->restaurant);
        abort_if($item->menu_category_id !== $category->id, 404);
        $data = $r->validate([
            'name' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'available' => 'boolean',
            'options' => 'array'
        ]);
        $item->update($data);
        return $item;
    }

    // DELETE /menu-categories/{category}/items/{item}
    public function destroy(Request $r, MenuCategory $category, MenuItem $item)
    {
        $this->authManage($r->user(), $category->restaurant);
        abort_if($item->menu_category_id !== $category->id, 404);
        $item->delete();
        return response()->noContent();
    }
}
