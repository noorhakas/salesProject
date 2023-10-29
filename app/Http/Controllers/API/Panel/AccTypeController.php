<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccType;
use App\Http\Requests\API\AccTypeRequest;
use App\Http\Resources\API\AccTypeResource;

class AccTypeController extends Controller
{
	public function index(Request $request)
	{
		$acc_types = AccType::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','Asc')->get();
        $data = AccTypeResource::collection($acc_types);
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(AccTypeRequest $request)
    {
		\DB::beginTransaction();
      try {
			$AccType = AccType::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new AccTypeResource($AccType));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show($id)
    {
		$AccType = AccType::find($id);
	   if(!$AccType)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),new AccTypeResource($AccType));
    }

	public function update(AccTypeRequest $request,$id) {
		\DB::beginTransaction();
      try {
		   $AccType = AccType::find($id);
		   if(!$AccType)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$AccType->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new AccTypeResource($AccType));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy($id)
    {

	 try {	
		$AccType = AccType::find($id);
		if(!$AccType)
           return $this->response_api(false, trans('messages.data_not_found'));

        $AccType->delete();
        return $this->response_api(true,  trans('messages.success'));
	 }catch (\Exception $e) {
			return $this->response_api(false, trans('messages.server_error'));
		}
    }


}