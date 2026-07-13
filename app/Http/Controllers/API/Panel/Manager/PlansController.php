<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Repository\Interfaces\PlanInterface;
use App\Models\Plan;
use App\Http\Requests\API\PlanApprovalRequest;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    public $IPlan;

    public function __construct(PlanInterface $IPlan)
    {
        $this->IPlan = $IPlan;
    }

    public function index(Request $request)
    {
        $manager = $request->user();
        $subordinateIds = $manager->getAllSubordinateIds();


        
        $statistics = $this->IPlan->statistics($request, $subordinateIds);
        $plans = $this->IPlan->getManagerPlans($request, $subordinateIds);
        $response = [
             'statistics' => $statistics,
             'plans'=>$plans['data']
        ];

        return $this->response_api($plans['status'], $plans['message'], $response ?? null);
    }

   
    public function show(Request $request, $plan_id)
    {
        $manager = $request->user();
        $subordinateIds = $manager->getAllSubordinateIds();

        $response = $this->IPlan->showForManager($plan_id, $subordinateIds);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

   
    public function acceptOrReject(PlanApprovalRequest $request)
    {
        $manager = $request->user();
        $subordinateIds = $manager->getAllSubordinateIds();

        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            return $this->response_api(false, trans('messages.data_not_found'));
        }

        if (!in_array($plan->user_id, $subordinateIds)) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        $response = $this->IPlan->AcceptOrRejectPlan($request);

        return $this->response_api($response['status'], $response['message']);
    }
}