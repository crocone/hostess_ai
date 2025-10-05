<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable=['hall_id','name','priority'];
    public function hall(){ return $this->belongsTo(Hall::class); }
    public function tables(){ return $this->hasMany(Table::class); }
    public function staff(){ return $this->belongsToMany(Staff::class)->withTimestamps(); }
}
