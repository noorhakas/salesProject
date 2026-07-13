<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Enums\StatusEnum;
use App\Enums\UserPositionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Sale;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    /**
     * Dashboard overview for the authenticated manager, aggregated across their
     * whole subtree of subordinates (all levels of the manager_id chain).
     */
    public function statisctics(Request $request)
    {
        $manager = $request->user();
        $today   = Carbon::today()->toDateString();

        $subordinateIds = $manager->getAllSubordinateIds();

        // Distinct branches / departments the team is assigned to.
        $branches = Branch::whereHas('users', fn ($q) => $q->whereIn('users.id', $subordinateIds))
            ->get(['id', 'name']);

        $departments = Department::whereHas('users', fn ($q) => $q->whereIn('users.id', $subordinateIds))
            ->get(['id', 'name']);

        $team_overview = [
            'total_sales'      => (float) Sale::whereIn('user_id', $subordinateIds)->sum('total_price'),
            'total_active_rep' => User::whereIn('id', $subordinateIds)
                ->where('status', StatusEnum::Active)
                ->where('position', UserPositionEnum::MedicalRep)
                ->count(),
            'total_branch'     => $branches->count(),
            'total_department' => $departments->count(),
            'branches'         => $branches,
            'departments'      => $departments,
        ];

        $visits_overview = $this->visitsOverview($subordinateIds, $today);

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'team_overview'   => $team_overview,
                'visits_overview' => $visits_overview,
            ]
        );
    }

    /**
     * Mutually-exclusive visit breakdown for a set of users:
     *  - active    : pending and due today
     *  - remaining : pending and scheduled in the future
     *  - missed    : explicitly missed, or pending and overdue
     *  - completed : visited
     */
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
    /**
     * List every user beneath the authenticated manager in the hierarchy
     * (full subtree, all levels), based on the manager_id chain.
     */
    public function subordinates(Request $request)
    {
        $manager = $request->user();

        $subordinateIds = $manager->getAllSubordinateIds();

        if (empty($subordinateIds)) {
            return $this->response_api(
                true,
                trans('messages.success'),
                UserResource::collection(
                    User::whereRaw('1 = 0')->paginate(20)
                )
            );
        }

        $limit = $request->filled('per_page') && is_numeric($request->per_page)
            ? $request->per_page
            : 20;

        $users = User::whereIn('id', $subordinateIds)
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('branches', fn ($b) => $b->where('branches.id', $request->branch_id));
            })
            ->filter($request)
            ->latest()
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            UserResource::collection($users)
        );
    }

    /**
     * Supervisors directly reporting to the authenticated manager, each with the
     * total number of users beneath them (their full recursive subtree).
     */
    public function supervisors(Request $request)
    {
        $manager = $request->user();

        $supervisors = User::where('manager_id', $manager->id)
            ->filter($request)
            ->latest()
            ->get();

        $data = $supervisors->map(fn ($supervisor) => [
            'id'            => $supervisor->id,
            'name'          => $supervisor->name,
            'user_name'     => $supervisor->user_name,
            'email'         => $supervisor->email,
            'status'        => $supervisor->status,
            'position_id'   => $supervisor->position,
            'position_name' => $supervisor->userposition ? $supervisor->userposition->name : '',
            'total_users'   => count($supervisor->getAllSubordinateIds()),
        ]);

        return $this->response_api(
            true,
            trans('messages.success'),
            $data
        );
    }

    /**
     * Sales reps directly reporting to a given supervisor. The supervisor must
     * belong to the authenticated manager's own subtree, otherwise access is denied.
     */
    public function supervisorReps(Request $request, User $supervisor)
    {
        $manager = $request->user();

        if (!in_array($supervisor->id, $manager->getAllSubordinateIds())) {
            return $this->response_api(
                false,
                trans('messages.permission_denied')
            );
        }

        $limit = $request->filled('per_page') && is_numeric($request->per_page)
            ? $request->per_page
            : 20;

        $reps = User::where('manager_id', $supervisor->id)
            ->filter($request)
            ->latest()
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            UserResource::collection($reps)
        );
    }

    /**
     * Aggregate statistics for the branches the authenticated manager belongs to:
     * total users and total visits (broken down by status) per branch, plus an
     * overall total across all of the manager's branches.
     */
    public function branchStatistics(Request $request)
    {
        $manager = $request->user();
        $today   = Carbon::today()->toDateString();

        // Limit visit aggregation to the manager's own subtree of users.
        $subordinateIds = $manager->getAllSubordinateIds();

        $branches = $manager->branches()->get();

        $perBranch = $branches->map(function ($branch) use ($subordinateIds, $today) {
            $userIds = $branch->users()
                ->whereIn('users.id', $subordinateIds)
                ->pluck('users.id')
                ->all();

            return [
                'branch_id'   => $branch->id,
                'branch_name' => $branch->name,
                'total_users' => count($userIds),
                'visits'      => $this->visitCounts($userIds, $today),
            ];
        });

        // Overall totals across the manager's whole subtree (deduplicated users).
        $overall = [
            'total_users' => count($subordinateIds),
            'visits'      => $this->visitCounts($subordinateIds, $today),
        ];

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'branches' => $perBranch->values(),
                'overall'  => $overall,
            ]
        );
    }

    /**
     * Conditional visit aggregates that mirror Visit::getStatusAttribute
     * (a pending visit dated before today is treated as missed).
     */
    private function visitCounts(array $userIds, string $today): array
    {
        if (empty($userIds)) {
            return [
                'total'   => 0,
                'pending' => 0,
                'visited' => 0,
                'holiday' => 0,
                'missed'  => 0,
            ];
        }

        $row = Visit::whereIn('user_id', $userIds)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as visited')
            ->selectRaw('SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as holiday')
            ->selectRaw('SUM(CASE WHEN status = 0 AND visit_date >= ? THEN 1 ELSE 0 END) as pending', [$today])
            ->selectRaw('SUM(CASE WHEN status = 5 OR (status = 0 AND visit_date < ?) THEN 1 ELSE 0 END) as missed', [$today])
            ->first();

        return [
            'total'   => (int) $row->total,
            'pending' => (int) $row->pending,
            'visited' => (int) $row->visited,
            'holiday' => (int) $row->holiday,
            'missed'  => (int) $row->missed,
        ];
    }
}
