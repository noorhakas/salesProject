<?php

namespace App\Http\Controllers\API\Panel\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\API\ProductRequest;
use App\Http\Requests\API\ProductNoteRequest;
use App\Repository\Interfaces\ProductInterface;
use Maatwebsite\Excel\Facades\Excel;

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

	

	public function show(Product $Product)
    {
		$response = $this->Iproduct->show($Product);
		return $this->SendResponse($response); 
    }



	public function addNotes(ProductNoteRequest $request){
	   $response = $this->Iproduct->addProductNote($request);
		return $this->SendResponse($response);
	}

	public function getProductNotes($id){
	    $response = $this->Iproduct->getAllProductNotes($id);
		return $this->SendResponse($response);
	}

         public function getProductFiles($id){
	    $response = $this->Iproduct->getAllProductFiles($id);
		return $this->SendResponse($response);
	}

}