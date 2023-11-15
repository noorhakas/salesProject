<?php

namespace App\Repository\Interfaces;

interface AccountInterface
{
     public function show($id);
     public function createAccount($request);
     public function updateAccount($request,$id);
     public function deleteAccount($id);
     public function getAll($request);
}