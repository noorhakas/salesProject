<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\CategoryInterface;
use App\Models\Category;
use App\Http\Resources\API\CategoryResource;

class CategoryRepository implements CategoryInterface
{
      
	  public function getAll($request)
	  {
		$category = Category::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','Asc')->get();
        $data = CategoryResource::collection($category);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createCategory($request){
		
		try {
			\DB::beginTransaction();
			  $category = Category::updateOrCreate(['name'=>$request->name],$request->validated());
			  \DB::commit();
			  return ['status'=>true,'message'=>trans('messages.success'),'data'=>new CategoryResource($category)];
		  } catch (\Exception $e) {
			  \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
		  }
	  }

	  public function updateCategory($request,$id){

		try {
			\DB::beginTransaction();
			  $category = Category::find($id);
			  if(!$category)
			       return ["status"=>false, "message"=>trans('messages.data_not_found')];
   
			   $category->update($request->validated());
			   \DB::commit();
			   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new CategoryResource($category)];
		   } catch (\Exception $e) {
			  \DB::rollback();
			   return ["status"=>false, "message"=>trans('messages.server_error')];
		   }
	  }

	public function show($id){

		$category = Category::find($id);
	   if(!$category)
	        return ["status"=>false, "message"=>trans('messages.data_not_found')];

		   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new CategoryResource($category)];	   
    }

	public function deleteCategory($id)
    {
		try {	
			$category = category::find($id);
			if(!$category)
			return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			$category->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
			}

    }


}