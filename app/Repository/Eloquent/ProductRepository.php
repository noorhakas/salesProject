<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\ProductInterface;
use App\Models\Product;
use App\Http\Resources\API\ProductResource;

class ProductRepository implements ProductInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		
		$products = (auth()->user()->access_all_data) ? Product::select('products.*') :  auth()->user()->products();
		$products = (Clone $products)->has('specialty')->when(request()->get('search'),fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->paginate($limit);
		   $data = ProductResource::collection($products);		
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createProduct($request){
		try {
			\DB::beginTransaction();
			   $product = Product::updateOrCreate(['name'=>$request->name],$request->validated());
			  \DB::commit();
			  return ['status'=>true,'message'=>trans('messages.success'),'data'=>new ProductResource($product)];
		  } catch (\Exception $e) {
			  \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
		  }
	  }

	  public function updateProduct($request,$Product){
		try {
			\DB::beginTransaction();
			if(!$product)
			  return ["status"=>false, "message"=>trans('messages.data_not_found')];
 
			 $product->update($request->validated());
			 \DB::commit();
			 return ["status"=>true, "message"=>trans('messages.success'),'data'=>new ProductResource($product)];
		 } catch (\Exception $e) {
			 \DB::rollback();
			 return ["status"=>false, "message"=>trans('messages.server_error')];
		 }
	  }

	public function show($Product){

		if(!$Product)
		return ["status"=>false, "message"=>trans('messages.data_not_found')];

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>new ProductResource($Product)];
    }

	public function deleteProduct($product)
    {
		try {	
			if(!$product)
		    return ["status"=>false, "message"=>trans('messages.data_not_found')];

				$product->delete();
				return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
			}

    }


}