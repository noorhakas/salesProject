<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Interfaces\BranchInterface;
use Illuminate\Http\Request;
use App\Http\Resources\API\Admin\DepartmentResource;

class BranchReportController extends Controller
{
    public function __construct(
        protected BranchInterface $IBranch
    ) {}

    /**
     * Branches report
     */
    public function index(Request $request)
    {
        $response = $this->IBranch->getBranchesReport($request);

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * Branch details
     */
    public function show(Request $request,$branchId) {
        $response = $this->IBranch->getBranchDetails($request,$branchId);

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * Branch departments
     */
    public function departments(
        Request $request,
        $branchId
    ) {
        $department = $this->IBranch->getBranchDepartments(
            $request,
            $branchId
        );

        $response = DepartmentResource::collection($department);

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * Branch products
     */
    public function products(
        Request $request,
        $branchId
    ) {
        $response = $this->IBranch->getBranchProducts(
            $request,
            $branchId
        );

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }
}