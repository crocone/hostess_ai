<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Zone;
use App\Models\Table;

class StaffAssignmentController extends Controller
{
    public function attachZones(Request $r, Staff $staff) {
        $this->authManage($r->user(), $staff->restaurant);
        $data = $r->validate(['zone_ids'=>'required|array','zone_ids.*'=>'integer|exists:zones,id']);
        $ids = collect($data['zone_ids'])->filter(function($id) use ($staff){
            return Zone::where('id',$id)->whereHas('hall', fn($q)=>$q->where('restaurant_id',$staff->restaurant_id))->exists();
        })->all();
        $staff->zones()->syncWithoutDetaching($ids);
        return $staff->load('zones');
    }
    public function attachTables(Request $r, Staff $staff) {
        $this->authManage($r->user(), $staff->restaurant);
        $data = $r->validate(['table_ids'=>'required|array','table_ids.*'=>'integer|exists:tables,id']);
        $ids = collect($data['table_ids'])->filter(function($id) use ($staff){
            return Table::where('id',$id)->whereHas('zone.hall', fn($q)=>$q->where('restaurant_id',$staff->restaurant_id))->exists();
        })->all();
        $staff->tables()->syncWithoutDetaching($ids);
        return $staff->load('tables');
    }
    public function detachZone(Request $r, Staff $staff, Zone $zone) {
        $this->authManage($r->user(), $staff->restaurant);
        $staff->zones()->detach($zone->id);
        return ['ok'=>true];
    }
    public function detachTable(Request $r, Staff $staff, Table $table) {
        $this->authManage($r->user(), $staff->restaurant);
        $staff->tables()->detach($table->id);
        return ['ok'=>true];
    }
    protected function authManage($u, $r){ 
        $role = $r->users()->where('users.id',$u->id)->first()?->pivot?->role;
        abort_if(!in_array($role,['owner','manager']),403); 
    }
}
