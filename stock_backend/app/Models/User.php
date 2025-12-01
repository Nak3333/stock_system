<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = [
        'username',
        'password_hash',
        'full_name',
        'email',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}
