<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\Admin\DepartmentResource;
use App\Repository\Interfaces\BranchInterface;
use Illuminate\Http\Request;

class BranchReportController extends Controller
{
    public function __construct(
        protected BranchInterface $IBranch
    ) {
    }

    /**
     * GET /admin/branches
     */
    public function index(Request $request)
    {
        $response = $this->IBranch->getBranchesReport(
            $request
        );

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * GET /admin/branches/{branch}
     */
    public function show(
        Request $request,
        $branchId
    ) {
        $response = $this->IBranch->getBranchDetails(
            $request,
            $branchId
        );

        if (
            isset($response['status']) &&
            ! $response['status']
        ) {
            return $this->response_api(
                false,
                $response['message']
            );
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * GET /admin/branches/{branch}/departments
     */
    public function departments(
        Request $request,
        $branchId
    ) {
        $department = $this->IBranch->getBranchDepartments(
            $request,
            $branchId
        );

        if (
            isset($department['status']) &&
            ! $department['status']
        ) {
            return $this->response_api(
                false,
                $department['message']
            );
        }

        $response = DepartmentResource::collection(
            $department
        );

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * GET /admin/branches/{branch}/products
     */
    public function products(
        Request $request,
        $branchId
    ) {
        $response = $this->IBranch->getBranchProducts(
            $request,
            $branchId
        );

        if (
            isset($response['status']) &&
            ! $response['status']
        ) {
            return $this->response_api(
                false,
                $response['message']
            );
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * GET /admin/branches/{branch}/sales-reps
     */
    public function salesReps(
        Request $request,
        $branchId
    ) {
        $response = $this->IBranch->getBranchSalesReps(
            $request,
            $branchId
        );

        if (
            isset($response['status']) &&
            ! $response['status']
        ) {
            return $this->response_api(
                false,
                $response['message']
            );
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }
}