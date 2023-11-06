<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\API\ProductRequest;
use App\Repository\Interfaces\ProductInterface;


class ProductController extends Controller
{
	public $Iproduct;
    public function __construct(ProductInterface $Iproduct)
    {
        $this->Iproduct = $Iproduct;
    }


	public function index(Request $request)
	{
		$response = $this->Iproduct->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(ProductRequest $request)
    {
		$response = $this->Iproduct->createProduct($request);
		return $this->SendResponse($response); 
      
    }

	public function show(Product $Product)
    {
		$response = $this->Iproduct->show($Product);
		return $this->SendResponse($response); 
    }

	public function update(ProductRequest $request,Product $product) {
		$response = $this->Iproduct->updateAccType($request,$product);
		return $this->SendResponse($response);
     
	}
	public function destroy(Product $product)
    {
		$response = $this->Iproduct->deleteProduct($product);
		return $this->SendResponse($response);
    }



}