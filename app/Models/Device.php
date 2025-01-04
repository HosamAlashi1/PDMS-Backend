<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'line_code',
        'latitude',
        'longitude',
        'device_type',
        'status',
        'response_time',
        'count',
        'group_id',
        'online_since',
        'offline_since',
        'last_examination_date',
        'insert_user_id',
        'update_user_id',
        'delete_user_id',
        'delete_date',
        'insert_date',
        'update_date',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }
}
