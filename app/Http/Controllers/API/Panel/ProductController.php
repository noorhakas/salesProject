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
		if (!auth()->user()->hasPermissionTo('display Product'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->Iproduct->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(ProductRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Product'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->Iproduct->createProduct($request);
		return $this->SendResponse($response); 
      
    }

	public function show(Product $Product)
    {
		if (!auth()->user()->hasPermissionTo('display Product'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->Iproduct->show($Product);
		return $this->SendResponse($response); 
    }

	public function update(ProductRequest $request,Product $product) {
		if (!auth()->user()->hasPermissionTo('update Product'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->Iproduct->updateProduct($request,$product);
		return $this->SendResponse($response);
     
	}
	public function destroy(Product $product)
    {
		if (!auth()->user()->hasPermissionTo('delete Product'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);


		$response = $this->Iproduct->deleteProduct($product);
		return $this->SendResponse($response);
    }



}