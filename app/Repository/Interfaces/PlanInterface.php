<?php

namespace App\Repository\Interfaces;

interface PlanInterface
{
      public function show($id);
      public function createNewPlan($request);
      public function getMyPlans($request);
	  public function getALL($request);
}