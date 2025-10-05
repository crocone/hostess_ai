<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Staff;
use App\Models\StaffWorkShift;

class StaffShiftController extends Controller
{
    public function index(Request $r, Restaurant $restaurant, Staff $staff) {
        $this->authView($r->user(), $restaurant);
        abort_if($staff->restaurant_id!==$restaurant->id,404);
        return $staff->shifts()->orderBy('date','desc')->paginate(100);
    }
    public function store(Request $r, Restaurant $restaurant, Staff $staff) {
        $this->authManage($r->user(), $restaurant);
        abort_if($staff->restaurant_id!==$restaurant->id,404);
        $data = $r->validate([
            'date'=>'required|date',
            'start_at'=>'required|date_format:H:i',
            'end_at'=>'required|date_format:H:i',
        ]);
        $shift = $staff->shifts()->create($data);
        return response()->json($shift, 201);
    }
    public function destroy(Request $r, Restaurant $restaurant, Staff $staff, StaffWorkShift $shift) {
        $this->authManage($r->user(), $restaurant);
        abort_if($staff->restaurant_id!==$restaurant->id,404);
        abort_if($shift->staff_id!==$staff->id,404);
        $shift->delete();
        return response()->noContent();
    }
    protected function role($user, Restaurant $r) { return $r->users()->where('users.id',$user->id)->first()?->pivot?->role; }
    protected function authView($u, Restaurant $r){ abort_if(!$this->role($u,$r),403); }
    protected function authManage($u, Restaurant $r){ abort_if(!in_array($this->role($u,$r),['owner','manager']),403); }
}
