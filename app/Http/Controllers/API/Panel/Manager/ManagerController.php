<?php

namespace App\Http\Controllers\API\Panel\Manager;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sale;
use App\Models\Visit;
use App\Models\Branch;
use App\Models\Department;
use App\Enums\StatusEnum;
use App\Enums\UserPositionEnum;
use App\Http\Controllers\Controller;

class ManagerController extends Controller
{
    public function statisctics(Request $request)
    {
        $manager = $request->user();
        $today   = Carbon::today()->toDateString();

        $subordinateIds = $this->getFilteredSubordinateIds($manager, $request);

        $branches = Branch::whereHas('users', function ($q) use ($subordinateIds) {
                $q->whereIn('users.id', $subordinateIds);
            })
            ->get(['id', 'name']);

        $departments = Department::whereHas('users', function ($q) use ($subordinateIds) {
                $q->whereIn('users.id', $subordinateIds);
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('branches', function ($branch) use ($request) {
                    $branch->where('branches.id', $request->branch_id);
                });
            })
            ->get(['id', 'name']);

        $teamOverview = [
            'total_sales' => (float) Sale::whereIn('user_id', $subordinateIds)->sum('total_price'),

            'total_active_rep' => User::whereIn('id', $subordinateIds)
                ->where('status', StatusEnum::Active)
                ->where('position', UserPositionEnum::MedicalRep)
                ->count(),

            'total_branch' => $branches->count(),

            'total_department' => $departments->count(),

            'branches' => $branches,

            'departments' => $departments,
        ];

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'team_overview' => $teamOverview,
                'visits_overview' => $this->visitsOverview($subordinateIds, $today),
            ]
        );
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
                'active_visit'     => 0,
                'remaing_vists'    => 0,
                'missed_visits'    => 0,
                'completed_visits' => 0,
            ];
        }

        $row = Visit::whereIn('user_id', $userIds)
            ->selectRaw('SUM(CASE WHEN status = 0 AND visit_date = ? THEN 1 ELSE 0 END) as active', [$today])
            ->selectRaw('SUM(CASE WHEN status = 0 AND visit_date > ? THEN 1 ELSE 0 END) as remaining', [$today])
            ->selectRaw('SUM(CASE WHEN status = 5 OR (status = 0 AND visit_date < ?) THEN 1 ELSE 0 END) as missed', [$today])
            ->selectRaw('SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as completed')
            ->first();

        return [
            'active_visit'     => (int) $row->active,
            'remaing_vists'    => (int) $row->remaining,
            'missed_visits'    => (int) $row->missed,
            'completed_visits' => (int) $row->completed,
        ];
    }
}