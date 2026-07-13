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
	  public function createUnplannedVisit($request);
	  public function getAllVisitsByUserId($request);
    public function getCurrentVisits();
	public function getUserVisitStatictics($request);
    public function getUserVisitAndSalesStatictics($request);
	public function getVisitsForManager($request, array $subordinateIds);
 
   public function showVisitForManager($id, array $subordinateIds);


	 
}