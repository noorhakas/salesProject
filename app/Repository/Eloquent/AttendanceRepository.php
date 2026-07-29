<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\V1\AttendanceInterface;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceRepository implements AttendanceInterface
{
    protected AttendanceCalculationService $calculationService;

    public function __construct(AttendanceCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Store a check-in / check-out attendance action.
     */
    public function storeAttendance(User $user, Request $request)
    {
        $authMethod = $request->auth_method ?? 'manual';

        // Device Validation
        // if ($user->device_id && $request->device_id != $user->device_id) {
        //     return 'device_not_allowed';
        // }
        //
        // if (!$user->device_id && !empty($request->device_id)) {
        //     $user->update(['device_id' => $request->device_id]);
        // }

        // Distance Validation
        // if (!is_standard_attendance_mode()) {
        //     $user->load('currentAssignment.project');
        //     $allowedDistance = setting('allowed_distance');
        //
        //     $proj_lat = $user->currentAssignment?->project?->latitude;
        //     $proj_lng = $user->currentAssignment?->project?->longitude;
        //
        //     $distance = $this->calculateDistance(
        //         $request->location_lat,
        //         $request->location_lng,
        //         $proj_lat,
        //         $proj_lng
        //     );
        //
        //     if ($distance > $allowedDistance) {
        //         return 'distance_not_allowed';
        //     }
        // }

        // Biometric Attempts Protection
        if (in_array($authMethod, ['fingerprint', 'face', 'biometric']) && !$request->biometric_verified) {
            $cacheKey = "biometric_attempts:user:{$user->id}:device:{$request->device_id}";
            $attempts = Cache::get($cacheKey, 0);

            $maxAttempts = 3;
            $lockMinutes = 2;

            if ($attempts >= $maxAttempts) {
                return 'biometric_locked';
            }

            Cache::put($cacheKey, $attempts + 1, now()->addMinutes($lockMinutes));
        }

        DB::beginTransaction();

        try {
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

            // ================= CHECK IN =================
            if ($request->action_type == 1) {
                if ($attendance) {
                    DB::rollBack();
                    return 'already_checked_in';
                }

                Attendance::create([
                    'user_id'         => $user->id,
                    'attendance_date' => $today,
                    'clock_in'        => now(),
                    'status'          => 1,
                ]);
            }

            // ================= CHECK OUT =================
            if ($request->action_type == 2) {
                if (!$attendance) {
                    DB::rollBack();
                    return 'must_check_in_first';
                }

                if ($attendance->clock_out) {
                    DB::rollBack();
                    return 'already_checked_out';
                }

                $attendance->update([
                    'clock_out' => now(),
                    'status'    => 1,
                ]);
            }

            AttendanceDetail::create($detailData);

            if (in_array($authMethod, ['fingerprint', 'face', 'biometric'])) {
                Cache::forget("biometric_attempts:user:{$user->id}:device:{$request->device_id}");
            }

            DB::commit();

            return 'success';
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Attendance Store Error', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

 
    public function getTodayAttendanceStatus(User $user): array
    {
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        $shift = $user->shift ?? null;

        return [
            'checked_in'      => !is_null($attendance?->clock_in),
            'checked_out'     => !is_null($attendance?->clock_out),
            'clock_in'        => $attendance?->clock_in,
            'clock_out'       => $attendance?->clock_out,
            'shift_clock_in'  => $shift?->time_from,
            'shift_clock_out' => $shift?->time_to,
        ];
    }

   
    public function attendanceLog(User $user, Request $request)
    {
        $limit = (is_numeric($request->per_page ?? null) && $request->per_page > 0)
            ? (int) $request->per_page
            : 20;

        $targetUserId = $request->user_id ?? $user->id;

        return Attendance::where('user_id', $targetUserId)
            ->when($request->start_date, fn ($q, $v) => $q->whereDate('attendance_date', '>=', $v))
            ->when($request->end_date, fn ($q, $v) => $q->whereDate('attendance_date', '<=', $v))
            ->orderBy('attendance_date', 'desc')
            ->paginate($limit);
    }



    public function show(int $attendanceId): array
    {
        $attendance = Attendance::find($attendanceId);

        if (!$attendance) {
            return ['status' => false, 'message' => trans('messages.data_not_found')];
        }

        return ['status' => true, 'message' => trans('messages.success'), 'data' => $attendance];
    }
    
}