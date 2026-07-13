<?php

namespace App\Repository\Interfaces;

interface SalesInterface
{
    public function storeUserSales($request);
    public function getUserProductsWithSales($accountId);
   public function getProductsWithAccountsSales($request);
    
}