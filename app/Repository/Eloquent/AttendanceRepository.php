<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\AttendanceInterface;
use App\Models\Attendance;
use App\Services\AttendanceCalculationService;
use App\Enums\AttendanceStatusEnum;
use Carbon\Carbon;

class AttendanceRepository implements AttendanceInterface
{
    protected AttendanceCalculationService $calculationService;

    public function __construct(AttendanceCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

  
    public function checkIn(int $userId, array $data): array
    {
        $today = Carbon::now()->toDateString();

        $existing = Attendance::where('user_id', $userId)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            return ['status' => false, 'message' => trans('messages.already_checked_in')];
        }

        $shift = $this->calculationService->resolveShift($userId, Carbon::parse($today));

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $userId, 'attendance_date' => $today],
            [
                'shift_id'     => $shift?->id,
                'clock_in'     => $data['clock_in'] ?? Carbon::now(),
                'status'       => AttendanceStatusEnum::HOLDON->value,
                'check_in_lat' => $data['lat'] ?? null,
                'check_in_lng' => $data['lng'] ?? null,
            ]
        );

        return ['status' => true, 'message' => trans('messages.success'), 'data' => $attendance];
    }

    public function checkOut(int $userId, array $data): array
    {
        $today = Carbon::now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return ['status' => false, 'message' => trans('messages.checkin_not_found')];
        }

        if ($attendance->clock_out) {
            return ['status' => false, 'message' => trans('messages.already_checked_out')];
        }

        $clockOut = $data['clock_out'] ?? Carbon::now();

        $result = $this->calculationService->calculateMinutes([
            'user_id'         => $userId,
            'attendance_date' => $today,
            'clock_in'        => $attendance->clock_in,
            'clock_out'       => $clockOut,
        ]);

        $attendance->update([
            'clock_out'        => $clockOut,
            'worked_minutes'   => $result['worked_minutes'],
            'late_minutes'     => $result['late_minutes'],
            'overtime_minutes' => $result['overtime_minutes'],
            'status'           => $result['status'],
            'attendance_type'  => $result['attendance_type'],
            'shift_id'         => $result['shift_id'],
            'check_out_lat'    => $data['lat'] ?? null,
            'check_out_lng'    => $data['lng'] ?? null,
        ]);

        return ['status' => true, 'message' => trans('messages.success'), 'data' => $attendance->fresh()];
    }


    public function getUserAttendance($request, ?int $userId = null): array
    {
        $limit = (is_numeric($request->per_page ?? null) && $request->per_page > 0) ? $request->per_page : 20;

        $targetUserId = $userId ?? $request->user_id ?? auth()->id();

        $attendances = Attendance::where('user_id', $targetUserId)
            ->when($request->start_date, fn ($q, $v) => $q->whereDate('attendance_date', '>=', $v))
            ->when($request->end_date, fn ($q, $v) => $q->whereDate('attendance_date', '<=', $v))
            ->orderBy('attendance_date', 'desc')
            ->paginate($limit);

        return ['status' => true, 'message' => trans('messages.success'), 'data' => $attendances];
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