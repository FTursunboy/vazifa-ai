<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'tg_nick', 'telegram_id', 'last_seen', 'email', 'daily_request_limit', 'daily_requests_used', 'daily_requests_reset_at', 'state', 'code', 'tariff',
        'is_authed', 'phone', 'position', 'workplace','tg_name'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'daily_request_limit' => 'integer',
        'daily_requests_used' => 'integer',
        'tariff' => 'integer',
    ];

    public function conversations() {
        return $this->hasMany(Conversation::class);
    }
}
