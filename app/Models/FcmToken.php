<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    protected $fillable = ['user_id', 'fcm_token', 'is_active', 'device_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

