<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $fillable=['restaurant_id','user_id','name','phone','role','active'];
    protected $casts=['active'=>'boolean'];
    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function shifts(){ return $this->hasMany(StaffWorkShift::class); }
    public function zones(){ return $this->belongsToMany(Zone::class)->withTimestamps(); }
    public function tables(){ return $this->belongsToMany(Table::class)->withTimestamps(); }
}
