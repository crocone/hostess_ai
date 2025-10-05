<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Zone;
use App\Models\Hall;

class ZoneController extends Controller
{
    public function index(Request $r, Hall $hall) {
        $this->authView($r->user(), $hall->restaurant);
        return $hall->zones()->orderBy('priority')->get();
    }
    public function store(Request $r, Hall $hall) {
        $this->authManage($r->user(), $hall->restaurant);
        $data = $r->validate(['name'=>'required','priority'=>'integer']);
        $data['hall_id']=$hall->id;
        $z = Zone::create($data);
        return response()->json($z,201);
    }
    public function show(Request $r, Hall $hall, Zone $zone) {
        $this->authView($r->user(), $hall->restaurant);
        abort_if($zone->hall_id!==$hall->id,404);
        return $zone->load('tables');
    }
    public function update(Request $r, Hall $hall, Zone $zone) {
        $this->authManage($r->user(), $hall->restaurant);
        abort_if($zone->hall_id!==$hall->id,404);
        $data = $r->validate(['name'=>'sometimes|required','priority'=>'integer']);
        $zone->update($data);
        return $zone;
    }
    public function destroy(Request $r, Hall $hall, Zone $zone) {
        $this->authManage($r->user(), $hall->restaurant);
        abort_if($zone->hall_id!==$hall->id,404);
        $zone->delete();
        return response()->noContent();
    }
    protected function role($user, $r) { return $r->users()->where('users.id',$user->id)->first()?->pivot?->role; }
    protected function authView($u, $r){ abort_if(!$this->role($u,$r),403); }
    protected function authManage($u, $r){ abort_if(!in_array($this->role($u,$r),['owner','manager']),403); }
}
