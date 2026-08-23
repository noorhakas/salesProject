<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\Admin\UserDetailResource;
use App\Http\Resources\API\SalesRepProfileResource;
use App\Http\Resources\API\SupervisorResource;
use App\Http\Resources\API\SupervisorSimpleResource;
use App\Models\User;
use App\Repository\Interfaces\AccountInterface;
use App\Repository\Interfaces\CustomerInterface;
use App\Repository\Interfaces\SalesRepInterface;
use App\Repository\Interfaces\SupervisorInterface;
use Illuminate\Http\Request;


class UserProfileController extends Controller
{
    public function __construct(
        protected SalesRepInterface $salesRepRepository,
        protected SupervisorInterface $supervisorRepository,
        protected AccountInterface $accountRepository,
        protected CustomerInterface $customerRepository,
    ) {
    }

    // =====================================================
    // Manager
    // =====================================================

    public function managerProfile(User $manager)
    {
        $manager->load([
            'userposition',
            'branches:id,name',
            'branchDepartments.branch:id,name',
            'branchDepartments.department:id,name',
        ]);

        return $this->response_api(
            true,
            trans('messages.success'),
            ['manager' => new UserDetailResource($manager)]
        );
    }

    public function managerSupervisors(Request $request, User $manager)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            SupervisorSimpleResource::collection(
                $this->supervisorRepository->supervisors($request, $manager)
            )
        );
    }

    public function managerSupervisorsStatistics(Request $request, User $manager)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            $this->supervisorRepository->statistics($request, $manager)
        );
    }

    public function managerReps(Request $request, User $manager)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            SalesRepProfileResource::collection(
                $this->salesRepRepository->getReps($request, $manager)
            )
        );
    }

    public function managerRepsStatistics(Request $request, User $manager)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            $this->salesRepRepository->statistics($request, $manager)
        );
    }

    public function managerAccounts(Request $request, User $manager)
    {
        $subordinateIds = $manager->getAllSubordinateIds();

        $response = $this->accountRepository->getAccountsForManager($request,$subordinateIds);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

     public function managerCustomers(Request $request, User $manager)
    {
        $subordinateIds = $manager->getAllSubordinateIds();

        $response = $this->customerRepository->getCustomersForManager($request,$subordinateIds);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }
    // =====================================================
    // Supervisor
    // =====================================================

    public function supervisorProfile(Request $request, User $supervisor)
    {
        $result = $this->supervisorRepository->supervisorProfile($request, $supervisor);

        if (! $result['status']) {
            return $this->response_api(false, $result['message']);
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            ['supervisor' => new SupervisorResource($result['supervisor'])]
        );
    }

    public function supervisorReps(Request $request, User $supervisor)
    {
        
        $result = $this->supervisorRepository->supervisorSalesRep($request, $supervisor, null);

        if (! $result['status']) {
            return $this->response_api(false, $result['message']);
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            SalesRepProfileResource::collection($result['sales_reps'])
        );
    }


    public function supervisorAccounts(Request $request, User $supervisor)
    {
        $subordinateIds = $supervisor->getAllSubordinateIds();

        $response = $this->accountRepository->getAccountsForManager($request,$subordinateIds);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

     public function supervisorCustomers(Request $request, User $supervisor)
    {
        $subordinateIds = $supervisor->getAllSubordinateIds();

        $response = $this->customerRepository->getCustomersForManager($request,$subordinateIds);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

    // =====================================================
    // Sales Rep
    // =====================================================

    public function salesRepProfile(Request $request, User $salesRep)
    {
        $result = $this->salesRepRepository->profile($request, $salesRep, null);

        if (! $result['status']) {
            return $this->response_api(false, $result['message']);
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            ['sales_rep' => new SalesRepProfileResource($result['sales_rep'])]
        );
    }
}