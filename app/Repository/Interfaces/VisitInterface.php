<?php

namespace App\Repository\Interfaces;

interface VisitInterface
{
     public function getvisitsByPlan($request);
	 public function submitVisit($request);
	 public function getvisitDtail($id);
	  public function getVisitCharts($request);
	  public function getAllVisits();
	  public function getVisitsByUserId($request);
	 
}