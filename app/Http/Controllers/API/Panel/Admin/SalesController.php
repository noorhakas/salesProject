<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Interfaces\SalesInterface;
use App\Http\Requests\API\SalesRequest;


class SalesController extends Controller
{
	public $Isale;
    public function __construct(SalesInterface $Isale)
    {
        $this->Isale = $Isale;
    }

	
	public function storeUserSales(SalesRequest $request)
    {
		$response = $this->Isale->storeUserSales($request);
		return $this->SendResponse($response);     
    }


    public function getUserProductSales($account_id){
        $response = $this->Isale->getUserProductsWithSales($account_id);
		return $this->SendResponse($response);  
    }


    public function getProductAccountsSales(Request $request){
           $response = $this->Isale->getProductsWithAccountsSales($request);
		return $this->SendResponse($response);  
    }

	

}