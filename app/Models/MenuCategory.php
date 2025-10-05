<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $fillable=['restaurant_id','name','priority'];
    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
    public function items(){ return $this->hasMany(MenuItem::class,'menu_category_id'); }
}
