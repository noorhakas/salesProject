<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\ProductInterface;
use App\Models\Product;
use App\Models\Visit;
use App\Models\ProductFiles;
use App\Models\ProductNotes;
use App\Models\User;
use App\Http\Resources\API\ProductResource;
use App\Http\Resources\API\ProductNoteResource;
use Carbon\Carbon;

class ProductRepository implements ProductInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		
		if(request()->get('user_id') && !empty(request()->get('user_id'))){
			$user = User::find(request()->get('user_id'));
			$products = $this->getProductQuery($user);
		}else{
			$products = $this->getProductQuery(auth()->user());
		}
		$products = (Clone $products)->has('specialty')->when(request()->get('search'),fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->paginate($limit);
		   $data = ProductResource::collection($products);		
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createProduct($request){
		try {
			\DB::beginTransaction();
			   $product = Product::updateOrCreate(['name'=>$request->name],$request->validated());

			    if(isset($request->files) && $request->has('files') && !empty($request->files)){
						foreach($request->file('files') as $i=>$file){
							$product->productfiles()->create(['file'=>$file]);
						}
				}
			  \DB::commit();
			  return ['status'=>true,'message'=>trans('messages.success'),'data'=>new ProductResource($product)];
		  } catch (\Exception $e) {
			  \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
		  }
	  }

	  public function updateProduct($request,$product){
		try {
			\DB::beginTransaction();
			if(!$product)
			  return ["status"=>false, "message"=>trans('messages.data_not_found')];
 
			 $product->update($request->validated());
			 if(isset($request->files)  && $request->has('files') && !empty($request->files)){
				$product->productfiles()->delete();
						foreach($request->file('files') as $i=>$file){
							$product->productfiles()->create(['file'=>$file]);
						}
				}
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


	
     public function addProductNote($request){
		try {
			\DB::beginTransaction();
			 $user =auth()->user();
				$product = Product::find($request->product_id);
				$product->productNotes()->syncWithoutDetaching([$user->id => ['note' => $request->note ,'created_at'=>Carbon::now()]]);
			 \DB::commit();
			 return ["status"=>true, "message"=>trans('messages.success')];
		 } catch (\Exception $e) {
			 \DB::rollback();
			 return ["status"=>false, "message"=>trans('messages.server_error')];
		 }
	  }


	  public function getAllProductNotes($id)
      {
        $limit = (is_numeric(request()->per_page) && (request()->per_page) > 0) ? request()->per_page : 20;
		
		$firstQuery = Visit::select('visits.notes as note','customers.name as customer_name','visits.created_at')
							->join('visit_details','visits.id','=','visit_details.visit_id')
							->join('customers','customers.id','=','visits.customer_id')
		                      ->whereNotNull('visits.notes')->where(['visit_details.item_id'=>$id,'visit_details.item_type'=>0]);

	   $secondQuery = ProductNotes::select('product_notes.note as note','users.name as customer_name','product_notes.created_at')->join('users','users.id','=','product_notes.user_id')
			             ->where('product_id',$id)->whereNotNull('note');
						

	    $finalQuery = $firstQuery->union($secondQuery);

					$productModel = \DB::table(\DB::raw("({$finalQuery->toSql()}) as activities"))->select('*')
					->mergeBindings($finalQuery->getQuery())
					->when(request()->search,fn($q, $v) =>
						 $q->where('note', 'like', "%{$v}%")->orWhere('customer_name', 'like', "%{$v}%"))
				   ->Paginate($limit);


	    return ["status"=>true, "message"=>trans('messages.success') ,'data'=>ProductNoteResource::collection($productModel)];					
	  }

	  protected function getProductQuery($user){
	    return ($user->access_all_data) ? Product::select('products.*') : 
				 $user->products();
	   
	}
}