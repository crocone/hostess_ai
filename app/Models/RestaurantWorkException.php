<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RestaurantWorkException extends Model
{
    protected $fillable=['restaurant_id','date','open_at','close_at','is_closed'];
    protected $casts=['date'=>'date','is_closed'=>'boolean'];
    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
}
