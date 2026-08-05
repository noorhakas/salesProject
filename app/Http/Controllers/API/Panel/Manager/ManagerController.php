<?php

namespace App\Http\Controllers\API\Panel\Manager;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Visit;
use App\Models\Plan;
use App\Enums\StatusEnum;
use App\Enums\PositionKey;
use App\Enums\PlanStatusEnum;
use App\Enums\AttendanceStatusEnum;
use App\Services\AttendanceStatusService;
use App\Http\Controllers\Controller;

class ManagerController extends Controller
{
    protected AttendanceStatusService $attendanceService;

    public function __construct(AttendanceStatusService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function statisctics(Request $request)
    {
        $manager = $request->user();
        $today = Carbon::today();

        $subordinateIds = $this->getFilteredSubordinateIds($manager, $request);

        $branches = $this->countFilteredBranches($manager, $request);
        $departments = $this->countFilteredDepartments($manager, $request);

        $teamOverview = $this->buildTeamOverview($subordinateIds, $branches, $departments, $today);

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'team_overview'   => $teamOverview,
                'visits_overview' => $this->visitsOverview($subordinateIds, $today->toDateString()),
            ]
        );
    }


     private function countFilteredBranches(User $manager, Request $request): int
    {
        if ($request->filled('branch_id')) {
            return $manager->branches()->whereKey($request->branch_id)->exists() ? 1 : 0;
        }

        if ($request->filled('department_id')) {
            return $manager->branches()
                ->whereHas('departments', function ($q) use ($request) {
                    $q->where('departments.id', $request->department_id);
                })
                ->count();
        }

        return $manager->branches()->count();
    }

    private function countFilteredDepartments(User $manager, Request $request): int
    {
        return $manager->departments()
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('branches', function ($b) use ($request) {
                    $b->where('branches.id', $request->branch_id);
                });
            })
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereKey($request->department_id);
            })
            ->count();
    }

    private function buildTeamOverview(array $subordinateIds, int $branches, int $departments, Carbon $today): array
    {
        $totalSalesReps = User::whereIn('id', $subordinateIds)
            ->whereHas('userposition', fn($q) =>
                    $q->where('ps_key', PositionKey::SALES_REP->value)
                )->count();

        $totalSupervisor = User::whereIn('id', $subordinateIds)
            ->whereHas('userposition', fn($q) =>
                    $q->where('ps_key', PositionKey::SUPERVISOR->value)
                )->count();

        [$activeReps, $absentReps] = $this->salesRepsAttendanceCounts($subordinateIds, $today);

        $pendingPlans = Plan::whereHas('user')->whereIn('user_id', $subordinateIds)
            ->where('status', PlanStatusEnum::Pending)
            ->count();

        return [
            'pending_plans'    => $pendingPlans,
            'total_sales_reps' => $totalSalesReps,
            'total_supervisor' => $totalSupervisor,
            'active_reps'      => $activeReps,
            'absent_reps'      => $absentReps,
            'total_branch'     => $branches,
            'total_department' => $departments,
        ];
    }

   
    private function salesRepsAttendanceCounts(array $subordinateIds, Carbon $today): array
    {
        $salesRepIds = User::whereIn('id', $subordinateIds)
            ->whereHas('userposition', fn($q) =>
                $q->where('ps_key', PositionKey::SALES_REP->value)
            )
            ->pluck('id');

        if ($salesRepIds->isEmpty()) {
            return [0, 0];
        }

        $active = 0;
        $absent = 0;

        $users = User::whereIn('id', $salesRepIds)->get();

        foreach ($users as $user) {
            $result = $this->attendanceService->resolve($user, $today);

            if ($result['is_day_off'] || $result['status'] === AttendanceStatusEnum::HOLIDAY) {
                // مش هيتحسب لا حضور ولا غياب في يوم الأجازة/الويكند
                continue;
            }

            if ($result['status'] === AttendanceStatusEnum::ABSENT) {
                $absent++;
            } else {
                $active++;
            }
        }

        return [$active, $absent];
    }

     private function getFilteredSubordinateIds(User $manager, Request $request): array
    {
        return User::query()
            ->whereIn('id', $manager->getAllSubordinateIds())
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('branches', function ($branch) use ($request) {
                    $branch->where('branches.id', $request->branch_id);
                });
            })
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('departments', function ($department) use ($request) {
                    $department->where('departments.id', $request->department_id);
                });
            })
            ->pluck('id')
            ->toArray();
    }

      private function visitsOverview(array $userIds, string $today): array
    {
        if (empty($userIds)) {
            return [
                'all'     => 0,
                'pending' => 0,
                'visited' => 0,
            ];
        }
 
        $row = Visit::whereIn('user_id', $userIds)
            ->selectRaw('SUM(CASE WHEN visit_date = ? THEN 1 ELSE 0 END) as all_count', [$today])
            ->selectRaw('SUM(CASE WHEN status = 0 AND visit_date = ? THEN 1 ELSE 0 END) as pending_count', [$today])
            ->selectRaw('SUM(CASE WHEN status = 2 AND visit_date = ? THEN 1 ELSE 0 END) as visited_count', [$today])
            ->first();
 
        return [
            'all'     => (int) $row->all_count,
            'pending' => (int) $row->pending_count,
            'visited' => (int) $row->visited_count,
        ];
    }

}