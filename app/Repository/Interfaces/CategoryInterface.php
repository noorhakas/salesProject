<?php

namespace App\Repository\Interfaces;

interface CategoryInterface
{
     public function show($id);
     public function createCategory($request);
     public function updateCategory($request,$id);
     public function deleteCategory($id);
     public function getAll($request);
}