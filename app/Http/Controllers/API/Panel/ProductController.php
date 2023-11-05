<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\API\ProductRequest;
use App\Http\Resources\API\ProductResource;


class ProductController extends Controller
{
	public function index()
	{
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		
		$products = (auth()->user()->access_all_data) ? Product::select('products.*') :  auth()->user()->products();
		$products = (Clone $products)->has('specialty')->when(request()->get('search'),fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->paginate($limit);
		   $data = ProductResource::collection($products);
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(ProductRequest $request)
    {
		\DB::beginTransaction();
      try {
			 $product = Product::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new ProductResource($product));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show(Product $Product)
    {
	   if(!$Product)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),new ProductResource($Product));
    }

	public function update(ProductRequest $request,Product $product) {
		\DB::beginTransaction();
      try {
		   if(!$product)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$product->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new ProductResource($product));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy(Product $product)
    {
		if(!$product)
           return $this->response_api(false, trans('messages.data_not_found'));

        $product->delete();
        return $this->response_api(true,  trans('messages.success'));
    }



}