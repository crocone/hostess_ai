<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\Zone;

class TableController extends Controller
{
    public function index(Request $r, Zone $zone) {
        $this->authView($r->user(), $zone->hall->restaurant);
        return $zone->tables()->orderBy('code')->get();
    }
    public function store(Request $r, Zone $zone) {
        $this->authManage($r->user(), $zone->hall->restaurant);
        $data = $r->validate([
            'code'=>'required',
            'seats'=>'integer|min:1',
            'is_active'=>'boolean',
            'attributes'=>'array'
        ]);
        $data['zone_id']=$zone->id;
        $t = Table::create($data);
        return response()->json($t,201);
    }
    public function show(Request $r, Zone $zone, Table $table) {
        $this->authView($r->user(), $zone->hall->restaurant);
        abort_if($table->zone_id!==$zone->id,404);
        return $table;
    }
    public function update(Request $r, Zone $zone, Table $table) {
        $this->authManage($r->user(), $zone->hall->restaurant);
        abort_if($table->zone_id!==$zone->id,404);
        $data = $r->validate([
            'code'=>'sometimes|required',
            'seats'=>'integer|min:1',
            'is_active'=>'boolean',
            'attributes'=>'array'
        ]);
        $table->update($data);
        return $table;
    }
    public function destroy(Request $r, Zone $zone, Table $table) {
        $this->authManage($r->user(), $zone->hall->restaurant);
        abort_if($table->zone_id!==$zone->id,404);
        $table->delete();
        return response()->noContent();
    }
    protected function role($user, $r) { return $r->users()->where('users.id',$user->id)->first()?->pivot?->role; }
    protected function authView($u, $r){ abort_if(!$this->role($u,$r),403); }
    protected function authManage($u, $r){ abort_if(!in_array($this->role($u,$r),['owner','manager']),403); }
}
