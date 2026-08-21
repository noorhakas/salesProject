<?php

namespace App\Repository;

use App\Http\Resources\API\BranchResource;
use App\Http\Resources\API\DepartmentResource;
use App\Http\Resources\API\ProductResource;
use App\Http\Resources\API\SupervisorSimpleResource;
use App\Http\Resources\API\UserSimpleResource;
use App\Http\Traits\PaginatesResults;
use App\Models\Branch;
use App\Models\Product;
use App\Repository\Interfaces\BranchInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchRepository implements BranchInterface
{
    use PaginatesResults;

    /**
     * Get branches report.
     */
    public function getBranchesReport(Request $request)
    {
        $branches = Branch::query()
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
                'id',
                'name',
                'address',
                'phone',
                'whatsapp',
            ]);

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
            ->select('user_branches.branch_id')
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN positions.ps_key = 'supervisor'
                        THEN 1
                        ELSE 0
                    END
                ) as supervisor_count"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN positions.ps_key = 'sales_rep'
                        THEN 1
                        ELSE 0
                    END
                ) as sales_rep_count"
            )
            ->groupBy('user_branches.branch_id')
            ->get()
            ->keyBy('branch_id');

        return $branches->map(
            function (Branch $branch) use ($usersCount) {
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
            }
        )->values();
    }

    /**
     * Get branch details.
     */
    public function getBranchDetails(Request $request, $branchId)
    {
        $branch = Branch::query()
            ->withCount('departments')
            ->findOrFail($branchId);

        $areaManager = $branch->users()
            ->with('userposition')
            ->whereHas(
                'userposition',
                fn (Builder $query) => $query->where(
                    'ps_key',
                    'area_manager'
                )
            )
            ->first();

        $supervisors = $branch->users()
            ->with('userposition')
            ->whereHas(
                'userposition',
                fn (Builder $query) => $query->where(
                    'ps_key',
                    'supervisor'
                )
            )
            ->get();

        $salesRepCount = $branch->users()
            ->whereHas(
                'userposition',
                fn (Builder $query) => $query->where(
                    'ps_key',
                    'sales_rep'
                )
            )
            ->count();

        return [
            'branch' => new BranchResource($branch),

            'department_count' => (int) $branch->departments_count,

            'supervisor_count' => $supervisors->count(),

            'sales_rep_count' => $salesRepCount,

            'area_manager' => $areaManager
                ? new UserSimpleResource($areaManager)
                : null,

            'supervisors' => SupervisorSimpleResource::collection(
                $supervisors
            ),
        ];
    }

    /**
     * Get branch departments.
     */
    public function getBranchDepartments(
        Request $request,
        $branchId
    ) {
        $branch = Branch::findOrFail($branchId);

        $departmentsQuery = $branch->departments()
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->where(
                    'name',
                    'like',
                    '%' . $request->input('search') . '%'
                )
            )
            ->withCount([
                'users',
                'products',
            ]);

        $departments = $this->paginateOrAll(
            $departmentsQuery,
            $request
        );

        return DepartmentResource::collection(
            $departments
        );
    }

    /**
     * Get branch products.
     */
    public function getBranchProducts(
        Request $request,
        $branchId
    ) {
        $productQuery = Product::query()
            ->with([
                'company',
                'category',
            ])
            ->whereHas(
                'departments.branches',
                fn (Builder $query) => $query->where(
                    'branches.id',
                    $branchId
                )
            )
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->where(
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

        return ProductResource::collection(
            $products
        );
    }
}