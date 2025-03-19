<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use  HasFactory, Notifiable ;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'personal_email',
        'company_email',
        'phone',
        'address',
        'password',
        'marital_status',
        'image',
        'role_id',
        'receives_emails',
        'last_email_sent',
        'email_frequency_hours',
        'is_logout',
        'is_active',
        'is_delete',
        'insert_user_id',
        'update_user_id',
        'delete_user_id',
        'delete_date',
        'fcm_token',
    ];

    protected $hidden = [
        'password', // Hide sensitive information
    ];


    public function getJWTIdentifier() {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return asset($value);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function errorLogs()
    {
        return $this->hasMany(ErrorLog::class);
    }

    public function forgets()
    {
        return $this->hasMany(Forget::class);
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

}
