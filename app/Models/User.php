<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    use InteractsWithMedia;

    protected $fillable = ['name', 'email', 'password', 'is_subscribed', 'is_accepted'];
    protected $hidden = ['password', 'remember_token'];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('avatar')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('s')
            ->performOnCollections('avatar')
            ->width(96)
            ->height(96)
            ->sharpen(10)
            ->format('webp')
            ->quality(80);
        $this->addMediaConversion('xs')
            ->performOnCollections('avatar')
            ->width(40)
            ->height(40)
            ->sharpen(10)
            ->format('webp')
            ->quality(80);
    }


    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class)->withPivot('role')->withTimestamps();
    }
}
