<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\SpecialtyInterface;
use App\Models\Specialty;
use App\Http\Resources\API\SpecialtyResource;

class SpecialtyRepository implements SpecialtyInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$specialty = Specialty::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->paginate($limit);
		        $data = SpecialtyResource::collection($specialty);

		   return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createSpecialty($request){

		try {
			\DB::beginTransaction();
			  $specialty = Specialty::updateOrCreate(['name'=>$request->name],$request->validated());
			 \DB::commit();
			 return ['status'=>true,'message'=>trans('messages.success'),'data'=>new SpecialtyResource($specialty)];
		 } catch (\Exception $e) {
			 \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
			}
	  }

	  public function updateSpecialty($request,$specialty){
		try {
			\DB::beginTransaction();
			   if(!$specialty)
			   return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
				$specialty->update($request->validated());
				\DB::commit();
				return ["status"=>true, "message"=>trans('messages.success'),'data'=>new SpecialtyResource($specialty)];
			} catch (\Exception $e) {
				\DB::rollback();
				return ["status"=>false, "message"=>trans('messages.server_error')];
			}
	  }

	public function show($specialty){
		if(!$specialty)
		    return ["status"=>false, "message"=>trans('messages.data_not_found')];

	   return  ["status"=>true, "message"=>trans('messages.success'),'data'=>new SpecialtyResource($specialty)];
	}

	public function deleteSpecialty($specialty)
    {
		try {	
			if(!$specialty)
		     return ["status"=>false, "message"=>trans('messages.data_not_found')];

			$specialty->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		}catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
		}
    }


}