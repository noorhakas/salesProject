<?php

namespace App\Repository\Interfaces;

interface AccountInterface
{
     public function show($id);
     public function createAccount($request);
     public function updateAccount($request,$id);
     public function deleteAccount($id);
     public function getAll($request);
     public function getUserAccount($request);
     public function getAllPharmacyGroups($request);
     public function createPharmacyGroup($request);
     public function updatePharmacyGroup($request,$id);
     public function showPharmacyGroup($request);
     public function deletePharmacyGroup($id);
     public function getAccountCharts();
}