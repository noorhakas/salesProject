<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\AttendanceReportInterface;
use App\Models\Attendance;
use App\Models\User;
use App\Enums\AttendanceStatusEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;


class AttendanceReportRepository implements AttendanceReportInterface
{
    public function dailySummary(Request $request): array
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $limit = (is_numeric($request->per_page ?? null) && $request->per_page > 0)
            ? (int) $request->per_page
            : 10;

        $employeesQuery = User::query()->where('is_admin', 0);

        $totalEmployees = (clone $employeesQuery)->count();

        $employeeIds = (clone $employeesQuery)->pluck('id');

        $attendancesByUser = Attendance::whereDate('attendance_date', $date)
            ->whereIn('user_id', $employeeIds)
            ->get()
            ->keyBy('user_id');

        $employees = (clone $employeesQuery)
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->get()
            ->map(function (User $user) use ($attendancesByUser) {
                $attendance = $attendancesByUser->get($user->id);

                $status = $attendance?->status ?? AttendanceStatusEnum::ABSENT;

                return [
                    // ASSUMPTION: replace `employee_number` below with the
                    // real column name on your `users` table if different.
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'status'      => $status->value,
                    'status_name' => $status->name,
                    'status_label'=> $status->label(),
                    'status_color'=> $status->color(),
                    'status_icon' => $status->icon(),
                    'clock_in'  => optional($attendance?->clock_in)->format('h:i A') ?? '',
                    'clock_out' => optional($attendance?->clock_out)->format('h:i A') ?? '',
                ];
            });

        // Optional status filter (expects the enum's int value, or "ALL"/empty for no filter).
        if ($request->filled('status') && strtoupper($request->status) !== 'ALL') {
            $employees = $employees->where('status', (int) $request->status)->values();
        }

        $counts = collect(AttendanceStatusEnum::cases())
            ->mapWithKeys(fn (AttendanceStatusEnum $case) => [$case->name => 0]);

        foreach ($employees as $employee) {
            $counts[$employee['status_name']] = ($counts[$employee['status_name']] ?? 0) + 1;
        }

        $presentCount = $counts['PRESENT'] ?? 0;
        $attendanceRate = $totalEmployees > 0
            ? round(($presentCount / $totalEmployees) * 100, 2)
            : 0;

        $page = (int) $request->input('page', 1);

        $paginatedEmployees = new LengthAwarePaginator(
            $employees->forPage($page, $limit)->values(),
            $employees->count(),
            $limit,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [
            'date'             => $date->toDateString(),
            'total_employees'  => $totalEmployees,
            'attendance_rate'  => $attendanceRate,
            'counts'           => [
                'present'                  => $counts['PRESENT'] ?? 0,
                'holiday'                  => $counts['HOLIDAY'] ?? 0,
                'absent'                   => $counts['ABSENT'] ?? 0,
                'leave_early'              => $counts['LEAVE_EARLY'] ?? 0,
                'late_arrival'             => $counts['LATE_ARRIVAL'] ?? 0,
                'late_arrival_leave_early' => $counts['LATE_ARRIVAL_LEAVE_EARLY'] ?? 0,
                'day_off'                  => $counts['WEEKEND'] ?? 0,
                'leave'                    => $counts['LEAVE'] ?? 0,
            ],
            'employees'        => $paginatedEmployees,
        ];
    }
}