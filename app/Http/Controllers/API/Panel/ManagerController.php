<?php

namespace App\Http\Controllers\API\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
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
