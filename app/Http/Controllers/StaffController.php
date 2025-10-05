<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Staff;

class StaffController extends Controller
{
    public function index(Request $r, Restaurant $restaurant) {
        $this->authView($r->user(), $restaurant);
        $q = $restaurant->staff()->orderBy('name');
        if ($r->has('q')) $q->where('name','like','%'.$r->get('q').'%');
        return $q->paginate(50);
    }
    public function store(Request $r, Restaurant $restaurant) {
        $this->authManage($r->user(), $restaurant);
        $data = $r->validate([
            'user_id'=>'nullable|exists:users,id',
            'name'=>'required',
            'phone'=>'nullable',
            'role'=>'required|in:waiter,hostess,manager',
            'active'=>'boolean'
        ]);
        $data['restaurant_id'] = $restaurant->id;
        $staff = Staff::create($data);
        return response()->json($staff, 201);
    }
    public function show(Request $r, Restaurant $restaurant, Staff $staff) {
        $this->authView($r->user(), $restaurant);
        abort_if($staff->restaurant_id!==$restaurant->id,404);
        return $staff->load('zones','tables','shifts');
    }
    public function update(Request $r, Restaurant $restaurant, Staff $staff) {
        $this->authManage($r->user(), $restaurant);
        abort_if($staff->restaurant_id!==$restaurant->id,404);
        $data = $r->validate([
            'name'=>'sometimes|required',
            'phone'=>'nullable',
            'role'=>'in:waiter,hostess,manager',
            'active'=>'boolean'
        ]);
        $staff->update($data);
        return $staff;
    }
    public function destroy(Request $r, Restaurant $restaurant, Staff $staff) {
        $this->authManage($r->user(), $restaurant);
        abort_if($staff->restaurant_id!==$restaurant->id,404);
        $staff->delete();
        return response()->noContent();
    }
    protected function role($user, Restaurant $r) { return $r->users()->where('users.id',$user->id)->first()?->pivot?->role; }
    protected function authView($u, Restaurant $r){ abort_if(!$this->role($u,$r),403); }
    protected function authManage($u, Restaurant $r){ abort_if(!in_array($this->role($u,$r),['owner','manager']),403); }
}
