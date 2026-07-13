<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\AttendanceCalculationService;
use Carbon\Carbon;
use App\Models\OvertimeRequest;
use Illuminate\Validation\ValidationException;

class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'shift_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'status',
        'worked_minutes',
        'overtime_minutes',
        'late_minutes',
        'note',
    ];

    protected $casts = [
        'status' => \App\Enums\AttendanceStatusEnum::class,
    ];

    /* =========================================
        BOOTED
    ========================================= */

    protected static function booted()
    {
        /*
        |--------------------------------------------------------------------------
        | Validate duplicate attendance
        |--------------------------------------------------------------------------
        */
        static::saving(function ($attendance) {

            $exists = self::query()
                ->where('user_id', $attendance->user_id)
                ->whereDate('attendance_date', $attendance->attendance_date)
                ->when(
                    $attendance->id,
                    fn ($q) => $q->where('id', '!=', $attendance->id)
                )
                ->exists();

            if ($exists) {

                throw ValidationException::withMessages([
                    'attendance_date' => 'Attendance already exists for this employee on this date.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate attendance values
            |--------------------------------------------------------------------------
            */

            $data = $attendance->toArray();

            $minutes = app(AttendanceCalculationService::class)->calculateMinutes($data);

            $attendance->worked_minutes = $minutes['worked_minutes'];
            $attendance->late_minutes = $minutes['late_minutes'];
            $attendance->overtime_minutes = $minutes['overtime_minutes'];
            $attendance->shift_id = $minutes['shift_id'];
            $attendance->status = $minutes['status'];

        });

        /*
        |--------------------------------------------------------------------------
        | After Create
        |--------------------------------------------------------------------------
        */
        static::created(function ($attendance) {

            self::syncMonthlyOvertime(
                $attendance->user_id,
                $attendance->attendance_date
            );
        });

        /*
        |--------------------------------------------------------------------------
        | After Update
        |--------------------------------------------------------------------------
        */
        static::updated(function ($attendance) {

            // if date changed update last month
            if ($attendance->wasChanged('attendance_date')) {

                self::syncMonthlyOvertime(
                    $attendance->user_id,
                    $attendance->getOriginal('attendance_date')
                );
            }

            //  update current month 
            self::syncMonthlyOvertime(
                $attendance->user_id,
                $attendance->attendance_date
            );
        });

        /*
        |--------------------------------------------------------------------------
        | After Delete
        |--------------------------------------------------------------------------
        */
        static::deleted(function ($attendance) {

            self::syncMonthlyOvertime(
                $attendance->user_id,
                $attendance->attendance_date
            );
        });
    }

    /* =========================================
        RELATIONS
    ========================================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function active_user()
    {
        return $this->belongsTo(User::class)
            ->where('status', 1);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    


    /* =========================================
        SCOPES
    ========================================= */

    public function scopeOvertimeOnly($query)
    {
        return $query->where('overtime_minutes', '>', 0);
    }

    /* =========================================
        MONTHLY OVERTIME SYNC
    ========================================= */

    protected static function syncMonthlyOvertime($userId, $date): void
    {
        $date = Carbon::parse($date);

        $totalMinutes = self::query()
            ->where('user_id', $userId)
            ->whereYear('attendance_date', $date->year)
            ->whereMonth('attendance_date', $date->month)
            ->sum('overtime_minutes');

        OvertimeRequest::updateOrCreate(
            [
                'user_id' => $userId,
                'year' => $date->year,
                'month' => $date->month,
            ],
            [
                'total_minutes' => $totalMinutes,
            ]
        );
    }
}