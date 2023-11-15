<?php

namespace App\Repository\Interfaces;

interface GiftInterface
{
     public function show($id);
     public function createGift($request);
     public function updateGift($request,$id);
     public function deleteGift($id);
     public function getAll($request);
}