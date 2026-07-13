<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\PublicHoliday;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class AttendanceCalculationService
{
    /*
    |--------------------------------------------------------------------------
    | Resolve Shift
    |--------------------------------------------------------------------------
    */

    public function resolveShift($userId, Carbon $date): ?Shift
    {
        return User::find($userId)?->shift;
    }

    /*
    |--------------------------------------------------------------------------
    | Weekly Off
    |--------------------------------------------------------------------------
    */

    public function isDayOff(Carbon $date, $userId): bool
    {
        return is_weekly_off_day($date);
    }

    /*
    |--------------------------------------------------------------------------
    | Official Holiday
    |--------------------------------------------------------------------------
    */

    public function isOfficialHoliday(Carbon $date): bool
    {
        return PublicHoliday::query()
            ->where('active', 1)
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Leave
    |--------------------------------------------------------------------------
    */

    protected function getLeaveStatus($userId, Carbon $date): ?AttendanceStatusEnum
    {
        // اربطيها بعدين بجدول الإجازات
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Attendance
    |--------------------------------------------------------------------------
    */

    public function calculate(array $data): array
    {
        $userId = $data['user_id'];

        $date = Carbon::parse($data['attendance_date']);

        $shift = $this->resolveShift($userId, $date);

        $shiftId = $shift?->id;

        $clockIn = !empty($data['clock_in'])
            ? Carbon::parse($date->toDateString().' '.$data['clock_in'])
            : null;

        $clockOut = !empty($data['clock_out'])
            ? Carbon::parse($date->toDateString().' '.$data['clock_out'])
            : null;

        /*
        |--------------------------------------------------------------------------
        | Leave
        |--------------------------------------------------------------------------
        */

        if ($leave = $this->getLeaveStatus($userId, $date)) {

            return [
                'status' => $leave->value,
                'shift_id' => $shiftId,
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Shift Required
        |--------------------------------------------------------------------------
        */

        if (! $shift) {

            return [
                'status' => AttendanceStatusEnum::UNPLANNED->value,
                'shift_id' => null,
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        $isHoliday = $this->isOfficialHoliday($date);

        $isWeekend = $this->isDayOff($date, $userId);

        /*
        |--------------------------------------------------------------------------
        | No Attendance
        |--------------------------------------------------------------------------
        */

        if (! $clockIn) {

            if ($isHoliday) {

                return [
                    'status' => AttendanceStatusEnum::HOLIDAY->value,
                    'shift_id' => $shiftId,
                    'worked_minutes' => 0,
                    'late_minutes' => 0,
                    'overtime_minutes' => 0,
                ];
            }

            if ($isWeekend) {
                return [
                    'status' => AttendanceStatusEnum::WEEKEND->value,
                    'shift_id' => $shiftId,
                    'worked_minutes' => 0,
                    'late_minutes' => 0,
                    'overtime_minutes' => 0,
                ];
            }

            return [
                'status' => AttendanceStatusEnum::ABSENT->value,
                'shift_id' => $shiftId,
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | No Checkout
        |--------------------------------------------------------------------------
        */

        if (! $clockOut) {

            return [
                'status' => AttendanceStatusEnum::HOLDON->value,
                'shift_id' => $shiftId,
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            $clockOut->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | Shift Time
        |--------------------------------------------------------------------------
        */

        $workStart = Carbon::parse(
            $date->toDateString().' '.$shift->time_from
        );

        $workEnd = Carbon::parse(
            $date->toDateString().' '.$shift->time_to
        );

        if ($workEnd->lessThanOrEqualTo($workStart)) {
            $workEnd->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | Worked Minutes
        |--------------------------------------------------------------------------
        */

        $workedMinutes = $clockIn->diffInMinutes($clockOut);

        /*
        |--------------------------------------------------------------------------
        | Grace Period
        |--------------------------------------------------------------------------
        */

        $grace = $shift->grace_minutes ?? 0;

        $allowedStart = $workStart->copy()->addMinutes($grace);

        $lateMinutes = 0;

        if ($clockIn->greaterThan($allowedStart)) {
            $lateMinutes = $allowedStart->diffInMinutes($clockIn);
        }

        /*
        |--------------------------------------------------------------------------
        | Early Leave
        |--------------------------------------------------------------------------
        */

        $leftEarly = $clockOut->lessThan($workEnd);

        /*
        |--------------------------------------------------------------------------
        | Overtime
        |--------------------------------------------------------------------------
        */

        $overtimeMinutes = 0;

        if ($clockOut->greaterThan($workEnd)) {
            $overtimeMinutes = $workEnd->diffInMinutes($clockOut);
        }

        /*
        |--------------------------------------------------------------------------
        | Weekend / Holiday Work
        |--------------------------------------------------------------------------
        */

        if ($isHoliday || $isWeekend) {

            $lateMinutes = 0;

            $overtimeMinutes = $workedMinutes;
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = AttendanceStatusEnum::WORKING;

        if (! $isHoliday && ! $isWeekend) {

            if ($lateMinutes > 0 && $leftEarly) {

                $status = AttendanceStatusEnum::LATE_ARRIVAL_LEAVE_EARLY;

            } elseif ($lateMinutes > 0) {

                $status = AttendanceStatusEnum::LATE_ARRIVAL;

            } elseif ($leftEarly) {

                $status = AttendanceStatusEnum::LEAVE_EARLY;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        return [

            'status' => $status->value,

            'shift_id' => $shiftId,

            'worked_minutes' => $workedMinutes,

            'late_minutes' => $lateMinutes,

            'overtime_minutes' => $overtimeMinutes,

        ];
    }
}