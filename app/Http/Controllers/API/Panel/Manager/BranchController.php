<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\DepartmentResource;
use App\Repository\Interfaces\BranchInterface;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        protected BranchInterface $branchRepository
    ) {
    }

    /**
     * GET /manager/branches
     */
    public function index(Request $request)
    {
        $manager = $request->user();

        $response = $this->branchRepository->getBranchesReport(
            $request,
            $manager
        );

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * GET /manager/branches/{branch}
     */
    public function branchDetail(
        Request $request,
        $branchId
    ) {
        $manager = $request->user();

        $response = $this->branchRepository->getBranchDetails(
            $request,
            $branchId,
            $manager
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
     * GET /manager/branches/{branch}/products
     */
    public function branchProducts(
        Request $request,
        $branchId
    ) {
        $manager = $request->user();

        $response = $this->branchRepository->getBranchProducts(
            $request,
            $branchId,
            $manager
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
     * GET /manager/branches/{branch}/departments
     */
    public function branchDepartments(
        Request $request,
        $branchId
    ) {
        $manager = $request->user();

        $response = $this->branchRepository->getBranchDepartments(
            $request,
            $branchId,
            $manager
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

        $response = DepartmentResource::collection(
            $response
        );

        return $this->response_api(
            true,
            trans('messages.success'),
            $response
        );
    }

    /**
     * GET /manager/branches/{branch}/sales-reps
     */
    public function branchSalesReps(
        Request $request,
        $branchId
    ) {
        $manager = $request->user();

        $response = $this->branchRepository->getBranchSalesReps(
            $request,
            $branchId,
            $manager
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