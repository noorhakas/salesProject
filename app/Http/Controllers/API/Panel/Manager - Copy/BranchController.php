<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\DepartmentResource;
use App\Http\Resources\API\UserSimpleResource;
use App\Http\Resources\API\SupervisorSimpleResource;
use App\Http\Resources\API\ProductResource;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        $subordinateIds = $user->getAllSubordinateIds();

        $branches = $user->branches()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->withCount('departments')
            ->get(['id', 'name','addrsss','phone']);

       $usersCount = DB::table('user_branches')
            ->join('users', 'users.id', '=', 'user_branches.user_id')
            ->join('positions', 'positions.id', '=', 'users.position')
            ->select(
                'user_branches.branch_id',
                DB::raw("SUM(CASE WHEN positions.ps_key = 'supervisor' THEN 1 ELSE 0 END) as supervisor_count"),
                DB::raw("SUM(CASE WHEN positions.ps_key = 'sales_rep' THEN 1 ELSE 0 END) as sales_rep_count")
            )
            ->whereIn('users.id', $subordinateIds)
            ->groupBy('user_branches.branch_id')
            ->get()
            ->keyBy('branch_id');

        $data = $branches->map(function ($branch) use ($usersCount) {

            $count = $usersCount->get($branch->id);

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone ?? '',
                'supervisor_count' => $count->supervisor_count ?? 0,
                'sales_rep_count' => $count->sales_rep_count ?? 0,
                'department_count' => $branch->departments_count,
            ];
        });

        return $this->response_api(true,trans('messages.success'),$data );
    }

    public function branchDetail(Request $request, Branch $branch)
    {
        $manager = $request->user();

        if (! $manager->branches()->whereKey($branch->id)->exists()) {
            return $this->response_api(false,trans('messages.permission_denied') );
        }

        $subordinateIds = $manager->getAllSubordinateIds();

        $branch->loadCount('departments');

        $branchManager = $branch->users()->with('userposition')
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'area_manager');
            })->first();

        $supervisors = $branch->users()->with('userposition')->whereIn('users.id', $subordinateIds)
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'supervisor');
            })->get();

        $salesRepCount = $branch->users()->whereIn('users.id', $subordinateIds)
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'sales_rep');
            })->count();

        return $this->response_api(true,trans('messages.success'),
            [
                'branch' => [
                    'id'   => $branch->id,
                    'name' => $branch->name,
                ],

                'supervisor_count' => $supervisors->count(),
                'sales_rep_count' => $salesRepCount,
                'department_count' => $branch->departments_count,

                'area_manager' => $branchManager ? new UserSimpleResource($branchManager) : null,
                'supervisors' => SupervisorSimpleResource::collection($supervisors),
            ]
        );
    }


    public function branchProducts(Request $request, Branch $branch)
    {
        $manager = $request->user();

        if (! $manager->branches()->whereKey($branch->id)->exists()) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        $limit = max((int) $request->input('per_page', 20), 1);

        $products = Product::with(['company', 'category'])
            ->whereHas('departments.branches', function ($q) use ($branch) {
                $q->where('branches.id', $branch->id);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
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

        $limit = max((int) $request->input('per_page', 20), 1);

        $departments = $branch->departments()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
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
