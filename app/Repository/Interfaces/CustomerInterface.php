<?php

namespace App\Repository\Interfaces;

interface CustomerInterface
{
     public function show($id);
     public function createCustomer($request);
     public function updateCustomer($request,$id);
     public function deleteCustomer($id);
     public function getAll($request);
}