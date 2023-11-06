<?php

namespace App\Repository\Interfaces;

interface ClassInterface
{
     public function show($id);
     public function createClass($request);
     public function updateClass($request,$id);
     public function deleteClass($id);
     public function getAll($request);
}