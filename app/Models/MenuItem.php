<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable=['restaurant_id','menu_category_id','name','description','price','available','options'];
    protected $casts=['options'=>'array','available'=>'boolean','price'=>'decimal:2'];
    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
    public function category(){ return $this->belongsTo(MenuCategory::class,'menu_category_id'); }
}
