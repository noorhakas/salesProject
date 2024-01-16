<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\ClassInterface;
use App\Models\Classes;

class ClassRepository implements ClassInterface
{
      
	  public function getAll($request)
	  {
		$data = Classes::select('id','name','frequency')
		                 ->when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		                 ->orderBy('created_at','ASC')->get();

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createClass($request){
		try {
			\DB::beginTransaction();
			   $classes = Classes::updateOrCreate(['name'=>$request->name],$request->validated());
			  \DB::commit();
			  return ['status'=>true,'message'=>trans('messages.success')];
		  } catch (\Exception $e) {
			  \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
		  }
	  }

	  public function updateClass($request,$id){
		try {
			\DB::beginTransaction();
			  $classes = Classes::find($id);
			  if(!$classes)
			  return ["status"=>false, "message"=>trans('messages.data_not_found')];
   
			   $classes->update($request->validated());
			   \DB::commit();
			   return ["status"=>true, "message"=>trans('messages.success'),'data'=>$classes];
		   } catch (\Exception $e) {
			   \DB::rollback();
			   return ["status"=>false, "message"=>trans('messages.server_error')];
		   }
	  }

	public function show($id){
		$classes = Classes::find($id);
	   if(!$classes)
           return ["status"=>false, "message"=>trans('messages.data_not_found')];

	      return ["status"=>true, "message"=>trans('messages.success'),'data'=>$classes];
	}

	public function deleteClass($id)
    {
		try {	
			$classes = Classes::find($id);
			if(!$classes)
				return ["status"=>false, "message"=>trans('messages.data_not_found')];

			$classes->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		}catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
		}
    }


}