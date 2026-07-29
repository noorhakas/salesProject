<?php

namespace App\Http\Controllers\API\Panel\User;

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


	

}