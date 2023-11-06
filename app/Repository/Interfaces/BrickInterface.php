<?php

namespace App\Repository\Interfaces;

interface BrickInterface
{
     public function show($id);
     public function createBrick($request);
     public function updateBrick($request,$id);
     public function deleteBrick($id);
     public function getAll($request);
}