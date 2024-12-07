<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'thread_id', 'status', 'last_run_id'];


    public function user() {
        return $this->belongsTo(User::class);
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }


}
