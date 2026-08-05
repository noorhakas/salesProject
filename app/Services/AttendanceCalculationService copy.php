<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\PublicHoliday;
use Carbon\Carbon;

class AttendanceCalculationService
{
    /**
     * Determine attendance status only.
     * No minutes / overtime calculation.
     */
    public function getStatus(
        int $userId,
        string $attendanceDate,
        $clockIn = null,
        $clockOut = null
    ): AttendanceStatusEnum {

        $date = Carbon::parse($attendanceDate);

        // Official Holiday
        if ($this->isOfficialHoliday($date)) {
            return AttendanceStatusEnum::HOLIDAY;
        }

        // Weekly Day Off
        if ($this->isDayOff($date)) {
            return AttendanceStatusEnum::WEEKEND;
        }

        $status = AttendanceStatusEnum::PRESENT;

        $shiftStart = setting('shift_time_from');
        $shiftEnd   = setting('shift_time_to');


        /*
        |--------------------------------------------------------------------------
        | Late Arrival
        |--------------------------------------------------------------------------
        */
        $late = false;

        if ($clockIn && $shiftStart) {

            $workStart = Carbon::parse(
                $date->toDateString() . ' ' . $shiftStart
            );

            $clockInTime = Carbon::parse($clockIn);

            if ($clockInTime->greaterThan($workStart)) {
                $late = true;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Leave Early
        |--------------------------------------------------------------------------
        */
        $leaveEarly = false;

        if ($clockOut && $shiftEnd) {

            $workEnd = Carbon::parse(
                $date->toDateString() . ' ' . $shiftEnd
            );

            $clockOutTime = Carbon::parse($clockOut);

            if ($clockOutTime->lessThan($workEnd)) {
                $leaveEarly = true;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Final Status
        |--------------------------------------------------------------------------
        */

        if ($late && $leaveEarly) {

            return AttendanceStatusEnum::LATE_ARRIVAL_LEAVE_EARLY;

        } elseif ($late) {

            return AttendanceStatusEnum::LATE_ARRIVAL;

        } elseif ($leaveEarly) {

            return AttendanceStatusEnum::LEAVE_EARLY;
        }


        return $status;
    }


    /**
     * Weekly off day check.
     */
    public function isDayOff(Carbon $date): bool
    {
        return is_weekly_off_day($date);
    }


    /**
     * Official holiday check.
     */
    public function isOfficialHoliday(Carbon $date): bool
    {
        return PublicHoliday::where('active', 1)
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->exists();
    }
}