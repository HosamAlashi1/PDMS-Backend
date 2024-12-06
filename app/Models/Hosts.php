<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hosts extends Model
{
    use HasFactory;

    protected $table = 'hosts';

    protected $fillable = [
        'hostname',
        'host_ip',
        'group_id',
        'group_title',
        'lat',
        'lng',
        'status',
        'count',
        'server',
        'last_examination_date',
        'insert_type',
        'insert_user_id',
        'insert_user_name',
        'insert_date',
    ];


    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
