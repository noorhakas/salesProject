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

class Branch1Controller extends Controller
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


    
}
