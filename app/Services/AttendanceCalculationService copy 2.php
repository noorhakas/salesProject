<?php

namespace App\Services;

use App\Models\PublicHoliday;
use App\Models\User;
use App\Enums\AttendanceStatusEnum;
use App\Models\Shift;
use Carbon\Carbon;


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

        $date = Carbon::parse($data['attendance_date']);

        // Resolve the effective shift / project by attendance mode.
        $shift     = $this->resolveShift($userId, $date);
        $shiftId   = $shift?->id;

        $clockIn = Carbon::parse(
            $date->toDateString() . ' ' . ($data['clock_in'] ?? now()->format('H:i:s'))
        )->seconds(0);

        $clockOut = isset($data['clock_out'])
            ? Carbon::parse($date->toDateString() . ' ' . $data['clock_out'])->seconds(0)
            : null;

        if (!$clockOut) {
            return [
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => AttendanceStatusEnum::HOLDON->value,
                'shift_id' => $shiftId,
                'attendance_type' => 'no_checkout',
            ];
        }

        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            $clockOut->addDay();
        }

        $workedMinutes = $clockIn->diffInMinutes($clockOut);

        $isDayOff = $this->isDayOff($date, $userId);
        $isHoliday = $this->isOfficialHoliday($date);

        /* =========================
             OFFICIAL HOLIDAY
        ========================= */
        if ($isHoliday) {
            return [
                'worked_minutes' => $workedMinutes,
                'late_minutes' => 0,
                'overtime_minutes' => $workedMinutes,
                'shift_id' => $shiftId,
                'status' => AttendanceStatusEnum::HOLIDAY->value,
                'attendance_type' => 'holiday_work',
            ];
        }

        /* =========================
             DAY OFF
        ========================= */
        if ($isDayOff) {
            return [
                'worked_minutes' => $workedMinutes,
                'late_minutes' => 0,
                'overtime_minutes' => $workedMinutes,
                'shift_id' => $shiftId,
                'status' => AttendanceStatusEnum::WEEKEND->value,
                'attendance_type' => 'day_off_work',
            ];
        }

        /* =========================
             NORMAL SHIFT
        ========================= */
        if ($shift) {

            $workStart = Carbon::parse($date->toDateString() . ' ' . $shift->time_from);
            $workEnd = Carbon::parse($date->toDateString() . ' ' . $shift->time_to);

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
                'worked_minutes' => $workedMinutes,
                'late_minutes' => $lateMinutes,
                'overtime_minutes' => max(0, $overtimeMinutes - $lateMinutes),

                'shift_id' => $shift->id,
                'status' => $status,
                'attendance_type' => 'scheduled',
            ];
        }

        /* =========================
             UNSCHEDULED WORK
        ========================= */
        return [
            'worked_minutes' => $workedMinutes,
            'late_minutes' => 0,
            'overtime_minutes' => $workedMinutes,

            'shift_id' => null,
            'status' => AttendanceStatusEnum::UNPLANNED->value,


            'attendance_type' => 'unscheduled',
        ];
    }
}
