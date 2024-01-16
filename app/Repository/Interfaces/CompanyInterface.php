<?php

namespace App\Repository\Interfaces;

interface CompanyInterface
{
     public function show($id);
     public function createCompany($request);
     public function updateCompany($request,$id);
     public function deleteCompany($id);
     public function getAll($request);
}