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


    public function accept(PlanApprovalRequest $request)
    {
        $plan = $this->authorizeManagedPlan($request);

        if ($plan instanceof \Illuminate\Http\JsonResponse) {
            return $plan;
        }

        $response = $this->IPlan->acceptPlan($request);

        return $this->response_api($response['status'], $response['message']);
    }

    public function reject(PlanApprovalRequest $request)
    {
        $plan = $this->authorizeManagedPlan($request);

        if ($plan instanceof \Illuminate\Http\JsonResponse) {
            return $plan;
        }

        $response = $this->IPlan->rejectPlan($request);

        return $this->response_api($response['status'], $response['message']);
    }

    
    protected function authorizeManagedPlan(Request $request)
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

        return $plan;
    }
}