<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable=['zone_id','code','seats','is_active','attributes'];
    protected $casts=['attributes'=>'array','is_active'=>'boolean'];
    public function zone(){ return $this->belongsTo(Zone::class); }
    public function staff(){ return $this->belongsToMany(Staff::class)->withTimestamps(); }
}
