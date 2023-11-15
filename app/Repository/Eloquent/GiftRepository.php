<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\GiftInterface;
use App\Models\Gift;
use App\Http\Resources\API\GiftResource;

class GiftRepository implements GiftInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$gifts = Gift::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','Asc')->paginate($limit);
        $data = GiftResource::collection($gifts);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createGift($request){
		
		try {
			\DB::beginTransaction();
			  $Gift = Gift::updateOrCreate(['name'=>$request->name],$request->validated());
			  \DB::commit();
			  return ['status'=>true,'message'=>trans('messages.success'),'data'=>new GiftResource($Gift)];
		  } catch (\Exception $e) {
			  \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
		  }
	  }

	  public function updateGift($request,$id){

		try {
			\DB::beginTransaction();
			  $gift = Gift::find($id);
			  if(!$gift)
			       return ["status"=>false, "message"=>trans('messages.data_not_found')];
   
			   $gift->update($request->validated());
			   \DB::commit();
			   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new GiftResource($AccType)];
		   } catch (\Exception $e) {
			   \DB::rollback();
			   return ["status"=>false, "message"=>trans('messages.server_error')];
		   }
	  }

	public function show($id){

		$gift = Gift::find($id);
	   if(!$gift)
	        return ["status"=>false, "message"=>trans('messages.data_not_found')];

		   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new GiftResource($gift)];	   
    }

	public function deleteGift($id)
    {
		try {	
			$gift = Gift::find($id);
			if(!$gift)
			return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			$gift->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
			}

    }


}