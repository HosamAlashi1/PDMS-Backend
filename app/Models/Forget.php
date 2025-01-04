<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forget extends Model
{
    use HasFactory;
    protected $table = 'forget';

    protected $fillable = [
        'user_id',
        'is_reset',
        'reset_date',
        'insert_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
