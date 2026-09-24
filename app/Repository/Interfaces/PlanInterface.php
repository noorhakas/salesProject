<?php

namespace App\Repository\Interfaces;

interface PlanInterface
{
      public function show($id);
      public function createNewPlan($request);
      public function updatePlan($request, $plan_id);
      public function getMyPlans($request);
	public function getALL($request);
	public function deletePlan($plan, bool $isAdmin = false, array $allowedUserIds = []);
	public function acceptPlan($request);
      public function rejectPlan($request);
      public function statistics($request, array $subordinateIds);
      public function getManagerPlans($request, array $subordinateIds);
      public function showForManager($plan_id, array $subordinateIds);

}