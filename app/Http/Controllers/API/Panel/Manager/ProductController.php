<?php

namespace App\Http\Controllers\API\Panel\Manager;

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

	public function show($id)
    {
        $product = Product::find($id);
        $response = $this->Iproduct->show($product);
        return $this->SendResponse($response);
    }

}