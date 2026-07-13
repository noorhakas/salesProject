<?php

namespace App\Repository\Interfaces;

interface ProductInterface
{
     public function show($id);
     public function createProduct($request);
     public function updateProduct($request,$id);
     public function deleteProduct($id);
     public function getAll($request);
	 public function addProductNote($request);
	 public function getAllProductNotes($id);
     public function getAllProductFiles($id);

}