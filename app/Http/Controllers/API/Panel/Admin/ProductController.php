<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\API\ProductRequest;
use App\Http\Requests\API\ProductNoteRequest;
use App\Repository\Interfaces\ProductInterface;
use App\Http\Exports\ProductExport;
use App\Http\Imports\ProductImport;
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

	public function store(ProductRequest $request)
    {
		
		$response = $this->Iproduct->createProduct($request);
		return $this->SendResponse($response); 
      
    }


	public function show($id)
    {
		
		$product = Product::find($id);
        $response = $this->Iproduct->showForAdmin($product);
        return $this->SendResponse($response);
    }

	public function update(ProductRequest $request,Product $product) {
		
		$response = $this->Iproduct->updateProduct($request,$product);
		return $this->SendResponse($response);
     
	}
	public function destroy(Product $product)
    {
		
		$response = $this->Iproduct->deleteProduct($product);
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

	public function exportProducts(){
        return Excel::download(new ProductExport(), 'products.xlsx');
    }
	
	public function importProducts(Request $request)
    {
        $request->validate([ 'file' => 'required|file|mimes:xls,xlsx' ]);
        $path = $request->file('file');
		try {
			\DB::beginTransaction();
				$products = Excel::import(new ProductImport, $path);
			\DB::commit();
			return  $this->SendResponse(['status'=>true,'message'=>trans('messages.success')]);
			} catch (\Exception $e) {
				\DB::rollback();
				return $this->SendResponse(['status'=>false,'message'=>trans('messages.server_error')]);
		}

    }

}