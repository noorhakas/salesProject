<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\BrickInterface;
use App\Models\Bricks;
use App\Models\User;
use App\Http\Resources\API\BricksResource;

class BrickRepository implements BrickInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		
		if(request()->get('user_id') && !empty(request()->get('user_id'))){
			$user = User::find(request()->get('user_id'));
			$bricks = $this->getBrickQuery($user);
		}else{
			$bricks = $this->getBrickQuery(auth()->user());
		}

		$bricks = (clone $bricks)->when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->paginate($limit);

		  $data = BricksResource::collection($bricks);			   
		  return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createBrick($request){
		
		try {
			 \DB::beginTransaction();
				Bricks::updateOrCreate(['name'=>$request->name],$request->validated());
				\DB::commit();
				return ['status'=>true,'message'=>trans('messages.success')];
			} catch (\Exception $e) {
				\DB::rollback();
				return ['status'=>false,'message'=>trans('messages.server_error')];
			}

	  }

	  public function updateBrick($request,$id){
		try {
			\DB::beginTransaction();
			  $brick = Bricks::find($id);
			  if(!$brick)
			      return ["status"=>false, "message"=>trans('messages.data_not_found')];
   
			   $brick->update($request->validated());
			   \DB::commit();
			   return ["status"=>true, "message"=>trans('messages.success'),'data'=>$brick];
		   } catch (\Exception $e) {
			   \DB::rollback();
			   return ["status"=>false, "message"=>trans('messages.server_error')];
		   }
	  }

	public function show($id){
		$bricks = Bricks::find($id);
	   if(!$bricks)
	        return ["status"=>false, "message"=>trans('messages.data_not_found')];

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>new BricksResource($bricks)];	
   }

	public function deleteBrick($id)
    {
		try {	
			$bricks = Bricks::find($id);
			if(!$bricks)
			return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			$bricks->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
		}
    }

	protected function getBrickQuery($user){
	    return ($user->access_all_data) ? Bricks::select('bricks.*') : 
				 $user->bricks();
	   
	}


}