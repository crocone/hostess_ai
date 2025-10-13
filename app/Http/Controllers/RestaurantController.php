<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    public function index(Request $r) {
        return $r->user()->restaurants()->withCount('staff','halls','menuItems')->get();
    }
    public function store(Request $r) {
        $data = $r->validate([
            'name'=>'required',
            'email'=>'nullable|string',
            'phone'=>'nullable|string',
            'address'=>'required|string',
            'attach' => 'nullable|file|max:12288|mimes:jpg,png',
        ]);
        $data['slug'] = Str::slug($data['name']);
        $restaurant = Restaurant::create($data);
        $r->user()->restaurants()->attach($restaurant->id, ['role'=>'owner']);
        return response()->json($restaurant, 201);
    }
    public function show(Request $r, Restaurant $restaurant) {
        $this->authorizeView($r->user(), $restaurant);
        return $restaurant->load('schedules','scheduleExceptions','halls.zones.tables','categories.items','staff');
    }
    public function update(Request $r, Restaurant $restaurant) {
        $this->authorizeManager($r->user(), $restaurant);
        $data = $r->validate([
            'name'=>'sometimes|required',
            'slug'=>'sometimes|alpha_dash|unique:restaurants,slug,'.$restaurant->id,
            'phone'=>'nullable',
            'timezone'=>'nullable',
            'settings'=>'array'
        ]);
        $restaurant->update($data);
        return $restaurant;
    }
    public function destroy(Request $r, Restaurant $restaurant) {
        $this->authorizeOwner($r->user(), $restaurant);
        $restaurant->delete();
        return response()->noContent();
    }

    protected function role($user, Restaurant $r) {
        return $r->users()->where('users.id',$user->id)->first()?->pivot?->role;
    }
    protected function authorizeView($u, Restaurant $r) {
        abort_if(!$this->role($u,$r), 403, 'Forbidden');
    }
    protected function authorizeManager($u, Restaurant $r) {
        abort_if(!in_array($this->role($u,$r),['owner','manager']), 403, 'Forbidden');
    }
    protected function authorizeOwner($u, Restaurant $r) {
        abort_if($this->role($u,$r)!=='owner', 403, 'Forbidden');
    }
}
