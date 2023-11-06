<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\AccTypeInterface;
use App\Models\AccType;
use App\Http\Resources\API\AccTypeResource;

class AccTypeRepository implements AccTypeInterface
{
      
	  public function getAll($request)
	  {
		$acc_types = AccType::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','Asc')->get();
        $data = AccTypeResource::collection($acc_types);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createAccType($request){
		
		try {
			\DB::beginTransaction();
			  $AccType = AccType::updateOrCreate(['name'=>$request->name],$request->validated());
			  \DB::commit();
			  return ['status'=>true,'message'=>trans('messages.success'),'data'=>new AccTypeResource($AccType)];
		  } catch (\Exception $e) {
			  \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
		  }
	  }

	  public function updateAccType($request,$id){

		try {
			\DB::beginTransaction();
			  $AccType = AccType::find($id);
			  if(!$AccType)
			       return ["status"=>false, "message"=>trans('messages.data_not_found')];
   
			   $AccType->update($request->validated());
			   \DB::commit();
			   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new AccTypeResource($AccType)];
		   } catch (\Exception $e) {
			   \DB::rollback();
			   return ["status"=>false, "message"=>trans('messages.server_error')];
		   }
	  }

	public function show($id){

		$AccType = AccType::find($id);
	   if(!$AccType)
	        return ["status"=>false, "message"=>trans('messages.data_not_found')];

		   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new AccTypeResource($AccType)];	   
    }

	public function deleteAccType($id)
    {
		try {	
			$AccType = AccType::find($id);
			if(!$AccType)
			return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			$AccType->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
			}

    }


}