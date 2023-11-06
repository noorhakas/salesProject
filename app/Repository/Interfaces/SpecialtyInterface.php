<?php

namespace App\Repository\Interfaces;

interface SpecialtyInterface
{
     public function show($id);
     public function createSpecialty($request);
     public function updateSpecialty($request,$id);
     public function deleteSpecialty($id);
     public function getAll($request);
}