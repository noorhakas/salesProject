<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\AttendanceInterface;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Traits\PaginatesResults;

class AttendanceRepository implements AttendanceInterface
{
    use PaginatesResults;
    protected AttendanceCalculationService $calculationService;


    public function __construct(AttendanceCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public function storeAttendance(User $user, Request $request)
    {
        $authMethod = $request->auth_method ?? 'manual';
        /*
        |--------------------------------------------------------------------------
        | Biometric Attempts Protection
        |--------------------------------------------------------------------------
        */

        if (
            in_array($authMethod, ['fingerprint', 'face', 'biometric'])
            && !$request->biometric_verified
        ) {

            $cacheKey ="biometric_attempts:user:{$user->id}:device:{$request->device_id}";
            $attempts = Cache::get($cacheKey, 0);

            if ($attempts >= 3) {
                return 'biometric_locked';
            }

            Cache::put($cacheKey,$attempts + 1,now()->addMinutes(2));
        }


        $today = Carbon::today()->toDateString();


        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();



        $detailData = [
            'user_id'            => $user->id,
            'day_date'           => $today,
            'action_time'        => now(),
            'action_type'        => $request->action_type,
            'location_lat'       => $request->location_lat,
            'location_lng'       => $request->location_lng,
            'auth_method'        => $authMethod,
            'biometric_verified' => true,
            'device_id'          => $request->device_id,
        ];



        /*
        |--------------------------------------------------------------------------
        | CHECK IN
        |--------------------------------------------------------------------------
        */

        if ($request->action_type == 1) {

            if ($attendance) {
                return 'already_checked_in';
            }


            $status = $this->calculationService->getStatus(
                $user->id,
                $today,
                now()
            );


            Attendance::create([
                'user_id'         => $user->id,
                'attendance_date' => $today,
                'clock_in'        => now(),
                'status'          => $status,
            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | CHECK OUT
        |--------------------------------------------------------------------------
        */

        if ($request->action_type == 2) {

            if (!$attendance) {
                return 'must_check_in_first';
            }

            $status = $this->calculationService->getStatus(
                $user->id,
                $today,
                $attendance->clock_in,
                now()
            );


            $attendance->update([
                'clock_out' => now(),
                'status'    => $status,
            ]);
        }



        AttendanceDetail::create($detailData);



        if (in_array($authMethod, ['fingerprint', 'face', 'biometric'])) {

            Cache::forget(
                "biometric_attempts:user:{$user->id}:device:{$request->device_id}"
            );
        }


        return 'success';
    }




    /**
     * Get today's attendance status
     */
    public function getTodayAttendanceStatus(User $user): array
    {
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();


        return [
            'checked_in'      => !is_null($attendance?->clock_in),
            'checked_out'     => !is_null($attendance?->clock_out),

            'clock_in'        => $attendance?->clock_in?->format('H:i:s')??'',
            'clock_out'       => $attendance?->clock_out?->format('H:i:s')??'',

            'status'          => $attendance?->status,

            'shift_clock_in'  => setting('shift_time_from'),
            'shift_clock_out' => setting('shift_time_to'),
        ];
    }




    /**
     * Attendance history
     */
    public function attendanceLog(User $user, Request $request)
    {
        $targetUserId = $request->user_id ?? $user->id;

        $query = Attendance::where('user_id', $targetUserId)
            ->when($request->start_date,fn ($q, $v) =>
                $q->whereDate('attendance_date', '>=', $v)
            )->when($request->end_date,
                fn ($q, $v) =>
                $q->whereDate('attendance_date', '<=', $v)
            )->orderBy('attendance_date', 'desc');

       return $this->paginateOrAll($query, $request);

    }

    /**
     * Show attendance
     */
    public function show(int $attendanceId): array
    {
        $attendance = Attendance::find($attendanceId);

        if (!$attendance) {
            return [
                'status'  => false,
                'message' => trans('messages.data_not_found')
            ];
        }

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => $attendance
        ];
    }
}