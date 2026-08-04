<?php

namespace App\Http\Controllers\API\Panel\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repository\Interfaces\ProductInterface;

class ProductController extends Controller
{
	public $Iproduct;
    public function __construct(ProductInterface $Iproduct)
    {
        $this->Iproduct = $Iproduct;
    }

	public function show(Product $Product)
    {
		$response = $this->Iproduct->show($Product);
		return $this->SendResponse($response); 
    }

}