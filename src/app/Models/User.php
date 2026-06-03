<?php

namespace App\Models;

use App\Models\Work;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function works()
    {
        return $this->hasMany(Work::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function scopeOnlyStaff($query)
    {
        return $query->where('role', 0)->orderBy('id');
    }
}