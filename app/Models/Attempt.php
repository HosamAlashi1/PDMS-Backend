<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    use HasFactory;

    protected $table = 'attempts';

    protected $fillable = [
        'hostname',
        'host_ip',
        'status',
        'is_mail_sent',
        'examination_date',
    ];

}
