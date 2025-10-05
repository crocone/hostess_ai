<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'restaurant_id','guest_name','guest_phone',
        'start_at','end_at','guests','status','notes',
        'source','created_by','idempotency_key'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function tables()     { return $this->belongsToMany(Table::class, 'reservation_tables')->withTimestamps(); }

    public function scopeActive($q) { return $q->whereIn('status', ['pending','confirmed']); }
}
