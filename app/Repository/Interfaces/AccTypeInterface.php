<?php

namespace App\Repository\Interfaces;

interface AccTypeInterface
{
     public function show($id);
     public function createAccType($request);
     public function updateAccType($request,$id);
     public function deleteAccType($id);
     public function getAll($request);

     public function showPosition($id);
     public function createPosition($request);
     public function updatePosition($request,$id);
     public function deletePosition($id);
     public function getPositionAll($request);
}