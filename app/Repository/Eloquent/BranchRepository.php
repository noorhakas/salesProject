<?php

namespace App\Repository\Eloquent;

use App\Http\Resources\API\BranchResource;
use App\Http\Resources\API\ProductResource;
use App\Http\Resources\API\SupervisorSimpleResource;
use App\Http\Resources\API\UserSimpleResource;
use App\Http\Traits\PaginatesResults;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Repository\Interfaces\BranchInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchRepository implements BranchInterface
{
    use PaginatesResults;

    /**
     * Get branches report.
     *
     * Admin:
     *     $user = null       => all branches
     *
     * Manager:
     *     $user = manager    => manager's branches only
     */
    public function getBranchesReport(
        Request $request,
        ?User $user = null
    ) {
        $subordinateIds = $user?->getAllSubordinateIds();

        /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */

        $branchesQuery = $user
            ? $user->branches()
            : Branch::query();

        $branches = $branchesQuery
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->where(
                    'name',
                    'like',
                    '%' . $request->input('search') . '%'
                )
            )
            ->withCount('departments')
            ->get([
                'branches.id',
                'branches.name',
                'branches.address',
                'branches.phone',
                'branches.whatsapp',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Supervisors / Sales Reps counts
        |--------------------------------------------------------------------------
        */

        $usersCount = DB::table('user_branches')
            ->join(
                'users',
                'users.id',
                '=',
                'user_branches.user_id'
            )
            ->join(
                'positions',
                'positions.id',
                '=',
                'users.position'
            )
            ->when(
                $subordinateIds !== null,
                fn ($query) => $query->whereIn(
                    'users.id',
                    $subordinateIds
                )
            )
            ->select('user_branches.branch_id')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN positions.ps_key = 'supervisor'
                        THEN 1
                        ELSE 0
                    END
                ) as supervisor_count
            ")
            ->selectRaw("
                SUM(
                    CASE
                        WHEN positions.ps_key = 'sales_rep'
                        THEN 1
                        ELSE 0
                    END
                ) as sales_rep_count
            ")
            ->groupBy('user_branches.branch_id')
            ->get()
            ->keyBy('branch_id');

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return $branches
            ->map(function (Branch $branch) use ($usersCount) {
                $userCount = $usersCount->get($branch->id);

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address ?? '',
                    'phone' => $branch->phone ?? '',
                    'whatsapp' => $branch->whatsapp ?? '',

                    'supervisor_count' =>
                        (int) ($userCount->supervisor_count ?? 0),

                    'sales_rep_count' =>
                        (int) ($userCount->sales_rep_count ?? 0),

                    'department_count' =>
                        (int) $branch->departments_count,
                ];
            })
            ->values();
    }

    /**
     * Get branch details.
     *
     * Admin:
     *     $user = null => all users
     *
     * Manager:
     *     $user = manager => only manager's subordinates
     */
    public function getBranchDetails(
        Request $request,
        $branchId,
        ?User $user = null
    ) {
        /*
        |--------------------------------------------------------------------------
        | Branch
        |--------------------------------------------------------------------------
        */

        $branch = Branch::query()
            ->withCount('departments')
            ->findOrFail($branchId);

        /*
        |--------------------------------------------------------------------------
        | Permission / Scope
        |--------------------------------------------------------------------------
        */

        if (
            $user &&
            ! $user->branches()->whereKey($branch->id)->exists()
        ) {
            return [
                'status' => false,
                'message' => trans('messages.permission_denied'),
            ];
        }

        $subordinateIds = $user?->getAllSubordinateIds();

        /*
        |--------------------------------------------------------------------------
        | Area Manager
        |--------------------------------------------------------------------------
        */

        $areaManager = $branch->users()
            ->with('userposition')
            ->whereHas(
                'userposition',
                fn (Builder $query) =>
                    $query->where('ps_key', 'area_manager')
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Supervisors
        |--------------------------------------------------------------------------
        */

        $supervisors = $branch->users()
            ->with('userposition')
            ->when(
                $subordinateIds !== null,
                fn ($query) =>
                    $query->whereIn('users.id', $subordinateIds)
            )
            ->whereHas(
                'userposition',
                fn (Builder $query) =>
                    $query->where('ps_key', 'supervisor')
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Sales Reps
        |--------------------------------------------------------------------------
        */

        $salesRepCount = $branch->users()
            ->when(
                $subordinateIds !== null,
                fn ($query) =>
                    $query->whereIn('users.id', $subordinateIds)
            )
            ->whereHas(
                'userposition',
                fn (Builder $query) =>
                    $query->where('ps_key', 'sales_rep')
            )
            ->count();

        return [
            'branch' => new BranchResource($branch),

            'department_count' =>
                (int) $branch->departments_count,

            'supervisor_count' =>
                $supervisors->count(),

            'sales_rep_count' =>
                $salesRepCount,

            'area_manager' => $areaManager
                ? new UserSimpleResource($areaManager)
                : null,

            'supervisors' =>
                SupervisorSimpleResource::collection($supervisors),
        ];
    }

    /**
     * Get branch departments.
     */
    public function getBranchDepartments(
        Request $request,
        $branchId,
        ?User $user = null
    ) {
        $branch = Branch::findOrFail($branchId);

        /*
        |--------------------------------------------------------------------------
        | Manager can only access his branches
        |--------------------------------------------------------------------------
        */

        if (
            $user &&
            ! $user->branches()->whereKey($branch->id)->exists()
        ) {
            return [
                'status' => false,
                'message' => trans('messages.permission_denied'),
            ];
        }

        $departmentsQuery = $branch->departments()
            ->when(
                $request->filled('search'),
                fn (Builder $query) =>
                    $query->where(
                        'name',
                        'like',
                        '%' . $request->input('search') . '%'
                    )
            )
            ->withCount([
                'users',
                'products',
            ]);

        return $this->paginateOrAll(
            $departmentsQuery,
            $request
        );
    }

    /**
     * Get branch sales reps.
     */
    public function getBranchSalesReps(
        Request $request,
        $branchId,
        ?User $user = null
    ) {
        $branch = Branch::findOrFail($branchId);

        /*
        |--------------------------------------------------------------------------
        | Manager can only access his branches
        |--------------------------------------------------------------------------
        */

        if (
            $user &&
            ! $user->branches()->whereKey($branch->id)->exists()
        ) {
            return [
                'status' => false,
                'message' => trans('messages.permission_denied'),
            ];
        }

        $subordinateIds = $user?->getAllSubordinateIds();

        $salesRepsQuery = $branch->users()
            ->with('userposition')
            ->when(
                $subordinateIds !== null,
                fn ($query) =>
                    $query->whereIn('users.id', $subordinateIds)
            )
            ->whereHas(
                'userposition',
                fn (Builder $query) =>
                    $query->where('ps_key', 'sales_rep')
            )
            ->when(
                $request->filled('search'),
                fn (Builder $query) =>
                    $query->where(
                        'users.name',
                        'like',
                        '%' . $request->input('search') . '%'
                    )
            )
            ->latest('users.created_at');

        $salesReps = $this->paginateOrAll(
            $salesRepsQuery,
            $request
        );

        return SupervisorSimpleResource::collection(
            $salesReps
        );
    }

    /**
     * Get branch products.
     */
    public function getBranchProducts(
        Request $request,
        $branchId,
        ?User $user = null
    ) {
        $branch = Branch::findOrFail($branchId);

        /*
        |--------------------------------------------------------------------------
        | Manager can only access his branches
        |--------------------------------------------------------------------------
        */

        if (
            $user &&
            ! $user->branches()->whereKey($branch->id)->exists()
        ) {
            return [
                'status' => false,
                'message' => trans('messages.permission_denied'),
            ];
        }

        $productQuery = Product::query()
            ->with([
                'company',
                'category',
            ])
            ->whereHas(
                'departments.branches',
                fn (Builder $query) =>
                    $query->where(
                        'branches.id',
                        $branchId
                    )
            )
            ->when(
                $request->filled('search'),
                fn (Builder $query) =>
                    $query->where(
                        'name',
                        'like',
                        '%' . $request->input('search') . '%'
                    )
            )
            ->distinct()
            ->latest();

        $products = $this->paginateOrAll(
            $productQuery,
            $request
        );

        return \App\Http\Resources\API\ProductResource::collection(
            $products
        );
    }
}