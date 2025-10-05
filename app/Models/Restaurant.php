<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable=['name','slug','phone','timezone','settings'];
    protected $casts=['settings'=>'array'];
    public function users(){ return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps(); }
    public function schedules(){ return $this->hasMany(RestaurantWorkSchedule::class); }
    public function scheduleExceptions(){ return $this->hasMany(RestaurantWorkException::class); }
    public function halls(){ return $this->hasMany(Hall::class); }
    public function categories(){ return $this->hasMany(MenuCategory::class); }
    public function menuItems(){ return $this->hasMany(MenuItem::class); }
    public function staff(){ return $this->hasMany(Staff::class); }
}
