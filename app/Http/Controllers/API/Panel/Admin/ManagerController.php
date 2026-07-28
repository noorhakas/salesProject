<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\ManagerResource;
use App\Http\Resources\API\SupervisorResource;
use App\Http\Resources\API\UserDetailResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Repository\Interfaces\ManagerInterface;

class ManagerController extends Controller
{
    public function __construct(
        protected ManagerInterface $managerRepository
    ) {
    }

    public function index(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            ManagerResource::collection(
                $this->managerRepository->managers($request)
            )
        );
    }

    
    public function show(Request $request, User $manager)
    {
        $result = $this->managerRepository->managerProfile($request, $manager);

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'manager' => new ManagerResource($result['manager']),
                'sales_reps' => UserDetailResource::collection($result['sales_reps']),
            ]
        );
    }
}