<?php

namespace App\Repository\Eloquent;

use App\Models\Branch;
use App\Models\User;
use App\Repository\Interfaces\BranchInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchRepository implements BranchInterface
{
    public function getBranchesReport(Request $request)
    {
        return Branch::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->withCount([
                'departments',
            ])
            ->with([
                'users.userposition',
            ])
            ->latest()
            ->get()
            ->map(function ($branch) {

                $users = $branch->users;

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address ?? '',
                    'phone' => $branch->phone ?? '',

                    'department_count' => $branch->departments_count,

                    'manager_count' => $users->filter(function ($user) {
                        return $user->userposition?->ps_key === 'area_manager';
                    })->count(),

                    'supervisor_count' => $users->filter(function ($user) {
                        return $user->userposition?->ps_key === 'supervisor';
                    })->count(),

                    'sales_rep_count' => $users->filter(function ($user) {
                        return $user->userposition?->ps_key === 'sales_rep';
                    })->count(),
                ];
            });
    }

    public function getBranchDetails(Request $request, $branchId)
    {
        $branch = Branch::query()
            ->with([
                'departments',
                'users.userposition',
            ])
            ->findOrFail($branchId);

        $users = $branch->users;

        return [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address ?? '',
                'phone' => $branch->phone ?? '',
            ],

            'departments' => $branch->departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                ];
            })->values(),

            'manager_count' => $users->filter(function ($user) {
                return $user->userposition?->ps_key === 'area_manager';
            })->count(),

            'supervisor_count' => $users->filter(function ($user) {
                return $user->userposition?->ps_key === 'supervisor';
            })->count(),

            'sales_rep_count' => $users->filter(function ($user) {
                return $user->userposition?->ps_key === 'sales_rep';
            })->count(),

            'managers' => $users
                ->filter(fn ($user) =>
                    $user->userposition?->ps_key === 'area_manager'
                )
                ->values()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'emp_no' => $user->emp_no,
                    'name' => $user->name,
                ]),

            'supervisors' => $users
                ->filter(fn ($user) =>
                    $user->userposition?->ps_key === 'supervisor'
                )
                ->values()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'emp_no' => $user->emp_no,
                    'name' => $user->name,
                ]),

            'sales_reps' => $users
                ->filter(fn ($user) =>
                    $user->userposition?->ps_key === 'sales_rep'
                )
                ->values()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'emp_no' => $user->emp_no,
                    'name' => $user->name,
                ]),
        ];
    }
}