<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'avatar', 'password', 'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function totalDonated(): float
    {
        return $this->donations()
            ->where('status', 'success')
            ->sum('amount');
    }
}
