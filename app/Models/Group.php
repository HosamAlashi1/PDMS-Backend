<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = [
        'title',
        'color',
        'coordinates',
        'city',
        'governorate',
        'is_active',
        'is_delete',
        'insert_user_id',
        'update_user_id',
        'delete_user_id',
        'delete_date',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

}
