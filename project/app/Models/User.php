<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    protected $fillable = [
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function setting(): HasOne
    {
        return $this->hasOne(Setting::class)->withDefault([
            'price' => '',
            'kcal_100g' => true,
            'carbs_100g' => false,
            'protein_100g' => false,
            'fat_100g' => false,
            'sugar_100g' => false,
            'fiber_100g' => false,
            'currency_sign' => '$',
            'language' => 'en',
            'auto_timer' => false,
        ]);
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->setting()->create();
        });
    }
}
