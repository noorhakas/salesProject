<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use App\Models\PublicHoliday;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceStatusService
{
    public function resolve(User $user, Carbon $date): array
    {
        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($attendance) {

            return [
                'status' => $attendance->status,
                'attendance' => $attendance,
                'holiday' => null,
                'leave' => null,
                'is_day_off' => false,
            ];
        }

        $holiday = PublicHoliday::query()
            ->where('active', 1)
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->first();

        if ($holiday) {
            return [
                'status' => AttendanceStatusEnum::HOLIDAY,
                'attendance' => null,
                'holiday' => $holiday,
                'leave' => null,
                'is_day_off' => false,
            ];
        }

        if (is_weekly_off_day($date)) {

            return [
                'status' => AttendanceStatusEnum::WEEKEND,
                'attendance' => null,
                'holiday' => null,
                'leave' => null,
                'is_day_off' => true,
            ];
        }

        return [
            'status' => AttendanceStatusEnum::ABSENT,
            'attendance' => null,
            'holiday' => null,
            'leave' => null,
            'is_day_off' => false,
        ];
    }
  
    public function statistics(Builder $query, Carbon $date): array
    {
        $stats = $query
            ->leftJoin('attendances', function ($join) use ($date) {
                $join->on('attendances.user_id', '=', 'users.id')
                    ->whereDate('attendances.attendance_date', $date);
            })
            ->selectRaw("
                COUNT(users.id) as total,
                SUM(CASE WHEN attendances.status = ? THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = ? THEN 1 ELSE 0 END) as leave_early,
                SUM(CASE WHEN attendances.status = ? THEN 1 ELSE 0 END) as late_arrival,
                SUM(CASE WHEN attendances.status = ? THEN 1 ELSE 0 END) as late_leave
            ", [
                AttendanceStatusEnum::PRESENT->value,
                AttendanceStatusEnum::LEAVE_EARLY->value,
                AttendanceStatusEnum::LATE_ARRIVAL->value,
                AttendanceStatusEnum::LATE_ARRIVAL_LEAVE_EARLY->value,
            ])
            ->first();

        $total = (int) $stats->total;
        $present = (int) $stats->present;
        $late = (int) $stats->leave_early + (int) $stats->late_arrival + (int) $stats->late_leave;

        return [
            'total'   => $total,
            'present' => $present,
            'late'    => $late,
            'absent'  => $total - $present - $late,
            'leave'   => 0, 
        ];
    }


    public function list(Builder $query,Carbon $date, Request $request) {

        $query->leftJoin('attendances', function ($join) use ($date) {

            $join->on('attendances.user_id','=','users.id')
                ->whereDate('attendance_date',$date);

        });

        if ($request->filled('status')) {

            switch ($request->status) {

                case 'present':

                    $query->where('attendances.status', AttendanceStatusEnum::PRESENT);

                    break;

                case 'late':

                    $query->whereIn('attendances.status',[
                        AttendanceStatusEnum::LATE_ARRIVAL,
                        AttendanceStatusEnum::LEAVE_EARLY,
                        AttendanceStatusEnum::LATE_ARRIVAL_LEAVE_EARLY,
                    ]);

                    break;

                case 'absent':

                    $query->whereNull('attendances.id');

                    break;

                case 'leave':

                    $query->whereIn('attendances.status',[
                        AttendanceStatusEnum::LEAVE,
                    ]);

                    break;
            }
        }

        if ($request->filled('search')) {

            $query->where('users.name','like','%'.$request->search.'%');

        }

        return $query->select('users.*')->paginate( $request->per_page ?? 20);
}

}

      


