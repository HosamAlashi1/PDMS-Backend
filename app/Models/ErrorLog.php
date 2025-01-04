<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'stack_trace',
        'request_path',
        'query_params',
        'http_method',
        'user_id',
        'insert_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
