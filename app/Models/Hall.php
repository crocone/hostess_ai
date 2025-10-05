<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    protected $fillable=['restaurant_id','name','priority'];
    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
    public function zones(){ return $this->hasMany(Zone::class); }
}
