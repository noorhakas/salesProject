<?php

namespace App\Services;

use App\Models\PublicHoliday;
use App\Models\User;
use App\Enums\AttendanceStatusEnum;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceCalculationService
{

    public function resolveShift($userId, $date): ?Shift
    {
        return $this->getUserHistoryShift($userId, $date);
    }

    public function getUserHistoryShift($userId, $date): ?Shift
    {
        if (!$userId || !$date) {
            return null;
        }

        return User::find($userId)?->historyAt($date)?->shift;
    }

    /* =========================
        CONTEXT RESOLUTION
        returns has_shift / is_day_off / is_holiday
    ========================= */
    public function resolveContext($userId, $date): array
    {
        return ['has_shift' => false, 'is_day_off' => false, 'is_holiday' => false];
    }

    /* =========================
        DAY OFF CHECK
        - standard mode   → global weekly off days (site setting)
        - assignment mode → employee assignment day_off
    ========================= */
    public function isDayOff(Carbon $date, $userId): bool
    {
        return is_weekly_off_day($date);
    }

    /* =========================
        OFFICIAL HOLIDAY CHECK (DB)
    ========================= */
    public function isOfficialHoliday(Carbon $date): bool
    {
        return PublicHoliday::where('active', 1)
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->exists();
    }

    /* =========================
        CALCULATIONS
    ========================= */
    public function calculateMinutes(array $data): array
    {
        $userId = $data['user_id'] ?? null;

        // ----------------------------------------------------
        // Guard: لو مفيش attendance_date صالحة، منقدرش نكمل خالص
        // ----------------------------------------------------
        if (empty($data['attendance_date'])) {
            Log::error('calculateMinutes: missing attendance_date', ['data' => $data]);

            return $this->fallbackResult(null);
        }

        try {
            $date = Carbon::parse($data['attendance_date']);
        } catch (\Throwable $e) {
            Log::error('calculateMinutes: invalid attendance_date', [
                'value' => $data['attendance_date'],
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResult(null);
        }

        // Resolve the effective shift / project by attendance mode.
        $shift   = $this->resolveShift($userId, $date);
        $shiftId = $shift?->id;

        // ----------------------------------------------------
        // Clock In (دايمًا لازم يكون Carbon صالح، أبدًا null)
        // ----------------------------------------------------
        try {
            $clockIn = $this->parseTimeValue($data['clock_in'] ?? null, $date)
                ?? now()->seconds(0);
        } catch (\Throwable $e) {
            Log::error('calculateMinutes: invalid clock_in', [
                'value' => $data['clock_in'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResult($shiftId);
        }

        // ----------------------------------------------------
        // Clock Out (ممكن يكون null فعليًا -> يعني لسه مفيش انصراف)
        // ----------------------------------------------------
        $clockOut = null;

        if (!empty($data['clock_out'])) {
            try {
                $clockOut = $this->parseTimeValue($data['clock_out'], $date);
            } catch (\Throwable $e) {
                Log::error('calculateMinutes: invalid clock_out', [
                    'value' => $data['clock_out'],
                    'error' => $e->getMessage(),
                ]);

                return $this->fallbackResult($shiftId);
            }
        }

        if (!$clockOut) {
            return [
                'worked_minutes'   => 0,
                'late_minutes'     => 0,
                'overtime_minutes' => 0,
                'status'           => AttendanceStatusEnum::HOLDON->value,
                'shift_id'         => $shiftId,
                'attendance_type'  => 'no_checkout',
            ];
        }

        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            $clockOut->addDay();
        }

        $workedMinutes = $clockIn->diffInMinutes($clockOut);

        $isDayOff  = $this->isDayOff($date, $userId);
        $isHoliday = $this->isOfficialHoliday($date);

        /* =========================
             OFFICIAL HOLIDAY
        ========================= */
        if ($isHoliday) {
            return [
                'worked_minutes'   => $workedMinutes,
                'late_minutes'     => 0,
                'overtime_minutes' => $workedMinutes,
                'shift_id'         => $shiftId,
                'status'           => AttendanceStatusEnum::HOLIDAY->value,
                'attendance_type'  => 'holiday_work',
            ];
        }

        /* =========================
             DAY OFF
        ========================= */
        if ($isDayOff) {
            return [
                'worked_minutes'   => $workedMinutes,
                'late_minutes'     => 0,
                'overtime_minutes' => $workedMinutes,
                'shift_id'         => $shiftId,
                'status'           => AttendanceStatusEnum::WEEKEND->value,
                'attendance_type'  => 'day_off_work',
            ];
        }

        /* =========================
             NORMAL SHIFT
        ========================= */
        // Guard: الشفت موجود لكن أوقاته ناقصة (time_from / time_to = null)
        // -> منعتبروش شفت عادي، نطلع unscheduled بدل ما نكراش
        if ($shift && $shift->time_from && $shift->time_to) {

            try {
                $workStart = Carbon::parse($date->toDateString() . ' ' . $shift->time_from);
                $workEnd   = Carbon::parse($date->toDateString() . ' ' . $shift->time_to);
            } catch (\Throwable $e) {
                Log::error('calculateMinutes: invalid shift times', [
                    'shift_id'   => $shift->id,
                    'time_from'  => $shift->time_from,
                    'time_to'    => $shift->time_to,
                    'error'      => $e->getMessage(),
                ]);

                $workStart = null;
                $workEnd   = null;
            }

            if ($workStart && $workEnd) {

                if ($workEnd->lessThanOrEqualTo($workStart)) {
                    $workEnd->addDay();
                }

                // Late  = arriving after the shift start.
                // Overtime = leaving after the shift end.
                $lateMinutes = $clockIn->greaterThan($workStart)
                    ? $workStart->diffInMinutes($clockIn)
                    : 0;

                $leftEarly = $clockOut->lessThan($workEnd);

                $overtimeMinutes = $clockOut->greaterThan($workEnd)
                    ? $workEnd->diffInMinutes($clockOut)
                    : 0;

                $status = AttendanceStatusEnum::PRESENT->value;
                if ($lateMinutes > 0 && $leftEarly) {
                    $status = AttendanceStatusEnum::LATE_ARRIVAL_LEAVE_EARLY;
                } elseif ($lateMinutes > 0) {
                    $status = AttendanceStatusEnum::LATE_ARRIVAL;
                } elseif ($leftEarly) {
                    $status = AttendanceStatusEnum::LEAVE_EARLY;
                }

                return [
                    'worked_minutes'   => $workedMinutes,
                    'late_minutes'     => $lateMinutes,
                    'overtime_minutes' => max(0, $overtimeMinutes - $lateMinutes),
                    'shift_id'         => $shift->id,
                    'status'           => $status,
                    'attendance_type'  => 'scheduled',
                ];
            }
        }

        /* =========================
             UNSCHEDULED WORK
        ========================= */
        return [
            'worked_minutes'   => $workedMinutes,
            'late_minutes'     => 0,
            'overtime_minutes' => $workedMinutes,
            'shift_id'         => null,
            'status'           => AttendanceStatusEnum::UNPLANNED->value,
            'attendance_type'  => 'unscheduled',
        ];
    }

    /**
     * نتيجة آمنة تترجع لو حصل أي خطأ غير متوقع في الحسابات،
     * بدل ما الـ Model event يكراش ويرمي 500 على المستخدم.
     */
    protected function fallbackResult(?int $shiftId): array
    {
        return [
            'worked_minutes'   => 0,
            'late_minutes'     => 0,
            'overtime_minutes' => 0,
            'status'           => AttendanceStatusEnum::HOLDON->value,
            'shift_id'         => $shiftId,
            'attendance_type'  => 'calculation_error',
        ];
    }

    protected function parseTimeValue($value, Carbon $date): ?Carbon
{
    if (empty($value)) {
        return null;
    }

    if ($value instanceof Carbon) {
        return $value->copy()->seconds(0);
    }

    // HH:mm أو HH:mm:ss
    if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
        return Carbon::parse($date->toDateString().' '.$value)->seconds(0);
    }

    // DateTime كامل
    return Carbon::parse($value)->seconds(0);
}
}