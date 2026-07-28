<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\SupervisorSimpleResource;
use App\Http\Resources\API\SupervisorResource;
use App\Http\Resources\API\UserDetailResource;
use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Http\Request;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;
use App\Repository\Interfaces\SupervisorInterface;

class SupervisorController extends Controller
{
    public function __construct(
        protected SupervisorInterface $supervisorRepository
    ) {
    }

    public function statistics(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            $this->supervisorRepository->statistics($request)
        );
    }

    public function supervisors(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            SupervisorSimpleResource::collection(
                $this->supervisorRepository->supervisors($request)
            )
        );
    }

    public function supervisorProfile(Request $request, User $supervisor)
    {
        $result = $this->supervisorRepository
            ->supervisorProfile($request, $supervisor);

        if (! $result['status']) {
            return $this->response_api(false, $result['message']);
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'supervisor' => new SupervisorResource($result['supervisor']),
                'sales_reps' => UserDetailResource::collection($result['sales_reps']),
            ]
        );
    }
}