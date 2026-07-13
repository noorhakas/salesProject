<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\DepartmentResource;
use App\Http\Resources\API\SupervisorResource;
use App\Http\Resources\API\UserDetailResource;
use App\Http\Resources\API\ProductResource;

use App\Models\Branch;
use App\Models\Position;

use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Branch55Controller extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();

    $subordinateIds = $user->getAllSubordinateIds();

    $branches = $user->branches()->with([
        'users.userposition',
        'departments',
    ])->get();

    $data = $branches->map(function ($branch) use ($subordinateIds) {

        $users = $branch->users->whereIn('id', $subordinateIds);

        $supervisors = $users->filter(function ($user) {
            return optional($user->userposition)->ps_key === 'supervisor';
        });

        $sales = $users->filter(function ($user) {
            return optional($user->userposition)->ps_key === 'sales_rep';
        });

        return [
            'id' => $branch->id,
            'name' => $branch->name,

            'supervisor_count' => $supervisors->count(),

            'sales_rep_count' => $sales->count(),

            'department_count' => $branch->departments->count(),
        ];
    });

    return $this->response_api(true,trans('messages.success'),$data);
}

public function branchDetail(Request $request, Branch $branch)
{
    $manager = $request->user();

    // التأكد أن الفرع يتبع المدير الحالي
    if (! $manager->branches()->where('branches.id', $branch->id)->exists()) {
        return $this->response_api(
            false,
            trans('messages.permission_denied')
        );
    }

    $subordinateIds = $manager->getAllSubordinateIds();

    $branch->load([
        'users.userposition',
        'departments' => function ($q) {
        $q->withCount([
            'users',
            'products',
        ])
        ->with([
            'products.company',
            'products.category',
        ]);
    },
    ]);

    $users = $branch->users->whereIn('id', $subordinateIds);

    // مدير الفرع
    $branchManager = $branch->users()
        ->whereHas('userposition', function ($q) {
            $q->where('ps_key', 'area_manager');
        })
        ->first();

    // المشرفون
    $supervisors = $users->filter(function ($user) {
        return optional($user->userposition)->ps_key === 'supervisor';
    })->values();

    // المندوبون
    $sales = $users->filter(function ($user) {
        return optional($user->userposition)->ps_key === 'sales_rep';
    })->values();

    // جميع منتجات الفرع (بدون تكرار)
    $products = $branch->departments
        ->flatMap(function ($department) {
            return $department->products;
        })
        ->unique('id')
        ->values();

    return $this->response_api(
        true,
        trans('messages.success'),
        [
            'branch' => [
                'id'   => $branch->id,
                'name' => $branch->name,
            ],

            'manager' => $branchManager
                ? new UserDetailResource($branchManager)
                : null,

            'supervisor_count' => $supervisors->count(),

            'sales_rep_count' => $sales->count(),

            'department_count' => $branch->departments->count(),

            'product_count' => $products->count(),

            'supervisors' => SupervisorResource::collection($supervisors),

            'sales_reps' => SupervisorResource::collection($sales),

            'departments' => DepartmentResource::collection($branch->departments),

            'products' => ProductResource::collection($products),
        ]
    );
}


public function branchSalesReps(Request $request, Branch $branch)
{
    $manager = $request->user();

    if (! $manager->branches()->whereKey($branch->id)->exists()) {
        return $this->response_api(false, trans('messages.permission_denied'));
    }

    $limit = $request->integer('per_page', 20);

    $subordinateIds = $manager->getAllSubordinateIds();

    $sales = User::with('userposition')
        ->whereIn('id', $subordinateIds)
        ->whereHas('branches', function ($q) use ($branch) {
            $q->where('branches.id', $branch->id);
        })
        ->whereHas('userposition', function ($q) {
            $q->where('ps_key', 'sales_rep');
        })
        ->latest()
        ->paginate($limit);

    return $this->response_api(
        true,
        trans('messages.success'),
        SupervisorResource::collection($sales)
    );
}


public function branchSupervisors(Request $request, Branch $branch)
{
    $manager = $request->user();

    if (! $manager->branches()->whereKey($branch->id)->exists()) {
        return $this->response_api(false, trans('messages.permission_denied'));
    }

    $limit = $request->integer('per_page', 20);

    $subordinateIds = $manager->getAllSubordinateIds();

    $supervisors = User::with('userposition')
        ->whereIn('id', $subordinateIds)
        ->whereHas('branches', function ($q) use ($branch) {
            $q->where('branches.id', $branch->id);
        })
        ->whereHas('userposition', function ($q) {
            $q->where('ps_key', 'supervisor');
        })
        ->latest()
        ->paginate($limit);

    return $this->response_api(
        true,
        trans('messages.success'),
        SupervisorResource::collection($supervisors)
    );
}

public function branchProducts(Request $request, Branch $branch)
{
    $manager = $request->user();

    if (! $manager->branches()->whereKey($branch->id)->exists()) {
        return $this->response_api(false, trans('messages.permission_denied'));
    }

    $limit = $request->integer('per_page', 20);

    $products = Product::with([
            'company',
            'category',
        ])
        ->whereHas('departments.branches', function ($q) use ($branch) {
            $q->where('branches.id', $branch->id);
        })
        ->distinct()
        ->latest()
        ->paginate($limit);

    return $this->response_api(
        true,
        trans('messages.success'),
        ProductResource::collection($products)
    );
}


public function branchDepartments(Request $request, Branch $branch)
{
    $manager = $request->user();

    if (! $manager->branches()->whereKey($branch->id)->exists()) {
        return $this->response_api(false, trans('messages.permission_denied'));
    }

    $limit = $request->integer('per_page', 20);

    $departments = $branch->departments()
        ->withCount([
            'users',
            'products',
        ])
        ->paginate($limit);

    return $this->response_api(
        true,
        trans('messages.success'),
        DepartmentResource::collection($departments)
    );
}


public function departmentDetail(Request $request, Department $department)
{
    $manager = $request->user();

    // التأكد أن القسم يتبع أحد فروع المدير
    // if (! $department->branches()
    //     ->whereIn('branches.id', $manager->branches()->pluck('branches.id'))
    //     ->exists()) {

    //     return $this->response_api(
    //         false,
    //         trans('messages.permission_denied')
    //     );
    // }

    $subordinateIds = $manager->getAllSubordinateIds();

    $department->load([
        'branches',
        'products.company',
        'products.category',
        'users.userposition',
    ]);

    // المستخدمين التابعين للمدير فقط
    $users = $department->users
        ->whereIn('id', $subordinateIds);

    // المشرفين
    $supervisors = $users->filter(function ($user) {
        return optional($user->userposition)->ps_key === 'supervisor';
    })->values();

    // المندوبين
    $sales = $users->filter(function ($user) {
        return optional($user->userposition)->ps_key === 'sales_rep';
    })->values();

  
    return $this->response_api(
        true,
        trans('messages.success'),
        [

            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],



            'supervisor_count' => $supervisors->count(),

            'sales_rep_count' => $sales->count(),

            'product_count' => $department->products->count(),


            'supervisors' => SupervisorResource::collection($supervisors)->resolve(),

            'sales_reps' => UserDetailResource::collection($sales)->resolve(),

            'products' => ProductResource::collection($department->products)->resolve(),


        ]
    );
}


use App\Models\Department;

public function departmentSalesReps(Request $request, Department $department)
{
    $manager = $request->user();

    // التأكد أن القسم يتبع أحد فروع المدير
    if (! $department->branches()
        ->whereIn('branches.id', $manager->branches()->pluck('branches.id'))
        ->exists()) {

        return $this->response_api(
            false,
            trans('messages.permission_denied')
        );
    }

    $subordinateIds = $manager->getAllSubordinateIds();

    $limit = is_numeric($request->per_page)
        ? max((int) $request->per_page, 1)
        : 20;

    $sales = $department->users()
        ->with('userposition')
        ->whereIn('users.id', $subordinateIds)
        ->whereHas('userposition', function ($q) {
            $q->where('ps_key', 'sales_rep');
        })
        ->latest('users.created_at')
        ->paginate($limit);

    return $this->response_api(
        true,
        trans('messages.success'),
        UserDetailResource::collection($sales)
    );
}


public function departmentProducts(Request $request, Department $department)
{
    $manager = $request->user();

    // التأكد أن القسم يتبع أحد فروع المدير
    if (! $department->branches()
        ->whereIn('branches.id', $manager->branches()->pluck('branches.id'))
        ->exists()) {

        return $this->response_api(
            false,
            trans('messages.permission_denied')
        );
    }

    $limit = is_numeric($request->per_page)
        ? max((int) $request->per_page, 1)
        : 20;

    $products = $department->products()
        ->with([
            'company',
            'category',
        ])
        ->latest()
        ->paginate($limit);

    return $this->response_api(
        true,
        trans('messages.success'),
        ProductResource::collection($products)
    );
}
    public function branchDetailo(Request $request, Branch $branch)
    {
        $manager = $request->user();

        if (! $manager->branches()->where('branches.id', $branch->id)->exists()) {
            return $this->response_api(
                false,
                trans('messages.permission_denied')
            );
        }

        $subordinateIds = $manager->getAllSubordinateIds();

         $branch->load([
          'users.userposition',
                'departments',
                'products',
            ])->loadCount([
                'departments',
                'products',
            ]);

        $users = $branch->users->whereIn('id', $subordinateIds);

        $branchManager = $branch->users()
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'area_manager');
            })
            ->first();

        // المشرفون
        $supervisors = $users->filter(function ($user) {
            return optional($user->userposition)->ps_key === 'supervisor';
        })->values();

        // المندوبون
        $sales = $users->filter(function ($user) {
            return optional($user->userposition)->ps_key === 'sales_rep';
        })->values();

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                ],
                'sales_rep_count' => $sales->count(),
                'supervisor_count' => $supervisors->count(),
                'department_count' => $branch->departments->count(),

                'manager' => $branchManager
                    ? new UserDetailResource($branchManager)
                    : null,

                'supervisors' => SupervisorResource::collection($supervisors),

                'sales_reps' => UserDetailResource::collection($sales),

                'departments' => DepartmentResource::collection($branch->departments),
            ]
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
