<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserSimpleResource;
use App\Http\Resources\API\ProductResource;
use App\Http\Resources\API\SupervisorSimpleResource;
use App\Http\Resources\API\DepartmentSimpleResource;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    

   public function show(Request $request, Branch $branch, Department $department)
    {
        $manager = $request->user();

        if (! $manager->branches()->whereKey($branch->id)->exists()) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        if (! $branch->departments()->whereKey($department->id)->exists()) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        $subordinateIds = $manager->getAllSubordinateIds();

        $department->loadCount('products');

        $counts = $department->users()
            ->whereIn('users.id', $subordinateIds)
            ->join('positions', 'users.position', '=', 'positions.id')
            ->whereIn('positions.ps_key', ['supervisor', 'sales_rep'])
            ->selectRaw("
                SUM(CASE WHEN positions.ps_key = 'supervisor' THEN 1 ELSE 0 END) as supervisor_count,
                SUM(CASE WHEN positions.ps_key = 'sales_rep' THEN 1 ELSE 0 END) as sales_rep_count
            ")
            ->first();

        $supervisor = $department->users()
            ->with('userposition')
            ->whereIn('users.id', $subordinateIds)
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'supervisor');
            })
            ->first();

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'department_name'  => $department->name,
                'branch'      => new DepartmentSimpleResource($branch),
                'product_count'    => $department->products_count,
                'sales_rep_count'  => (int) ($counts->sales_rep_count ?? 0),
                'supervisor_count' => (int) ($counts->supervisor_count ?? 0),
                'supervisors'      => new SupervisorSimpleResource($supervisor),
            ]
        );
    }

    public function departmentSalesReps(Request $request, Branch $branch, Department $department)
    {
        $manager = $request->user();

        if (! $manager->branches()->whereKey($branch->id)->exists()) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        if (! $branch->departments()->whereKey($department->id)->exists()) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        $subordinateIds = $manager->getAllSubordinateIds();

        $limit = max((int) $request->input('per_page', 20), 1);

        $sales = $department->users()
            ->with('userposition')
            ->whereIn('users.id', $subordinateIds)
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'sales_rep');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('users.name', 'like', '%' . $request->search . '%');
            })
            ->latest('users.created_at')
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            UserSimpleResource::collection($sales)
        );
    }


    public function departmentProducts(Request $request, Branch $branch, Department $department)
    {
        $manager = $request->user();

        if (! $manager->branches()->whereKey($branch->id)->exists()) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        if (! $branch->departments()->whereKey($department->id)->exists()) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        $limit = max((int) $request->input('per_page', 20), 1);

        $products = $department->products()
            ->with(['company', 'category'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            ProductResource::collection($products)
        );
    }




    
}
