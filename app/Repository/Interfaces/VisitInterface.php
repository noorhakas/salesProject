<?php

namespace App\Repository\Interfaces;

interface VisitInterface
{
     public function getvisitsByPlan($request);
	 public function getvisitDtail($id);
}