<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RestaurantWorkSchedule extends Model
{
    protected $fillable=['restaurant_id','weekday','open_at','close_at','is_closed'];
    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
}
