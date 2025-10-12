<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ConfirmCode extends Model
{
    protected $fillable = [
        'user_id',
        'send_type',
        'ip_address',
        'code',
        'used',
        'phone',
        'email',
        'type',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
