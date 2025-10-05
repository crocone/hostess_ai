<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StaffWorkShift extends Model
{
    protected $fillable=['staff_id','date','start_at','end_at'];
    protected $casts=['date'=>'date'];
    public function staff(){ return $this->belongsTo(Staff::class); }
}
