<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDetail extends Model
{
    protected $table = 'attendance_details';

    protected $fillable = [
        'user_id','day_date','action_time','action_type','location_lat','location_lng',
        'faceprint','fingerprint','auth_method','biometric_verified','device_id'
    ];

    protected $casts = [
        'day_date'    => 'date',
        'action_time' => 'datetime',
        'biometric_verified' => 'boolean',
    ];

    public const AUTH_METHODS = [
        'fingerprint',
        'face',
        'manual',
    ];

    public static function getAuthMethods(): array
    {
        return self::AUTH_METHODS;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

