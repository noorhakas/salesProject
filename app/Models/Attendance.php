<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'status',
        'note',
    ];

    protected $casts = [
        'status'           => \App\Enums\AttendanceStatusEnum::class,
        'attendance_date'  => 'date',
        'clock_in'         => 'datetime',
        'clock_out'        => 'datetime',
    ];

    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function active_user()
    {
        return $this->belongsTo(User::class)
            ->where('status', 1);
    }

    


}