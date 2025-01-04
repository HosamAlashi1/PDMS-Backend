<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    use HasFactory;

    protected $table = 'attempts';

    protected $fillable = [
        'ip_address',
        'status',
        'response_time',
        'is_alert_sent',
        'alert_sent_date',
        'examination_date',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

}
