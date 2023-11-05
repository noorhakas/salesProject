<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Http\Resources\API\SpecialtyResource;
use App\Http\Requests\API\SpecialtyRequest;

class SpecialtyController extends Controller
{
	public function index(Request $request)
	{
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$specialty = Specialty::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->paginate($limit);
		   $data = SpecialtyResource::collection($specialty);
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(SpecialtyRequest $request)
    {
		\DB::beginTransaction();
      try {
			 $specialty = Specialty::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new SpecialtyResource($specialty));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show(Specialty $specialty)
    {
	   if(!$specialty)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),new SpecialtyResource($specialty));
    }

	public function update(SpecialtyRequest $request,Specialty $specialty) {
		\DB::beginTransaction();
      try {
		   if(!$specialty)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$specialty->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new SpecialtyResource($specialty));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy(Specialty $specialty)
    {
		if(!$specialty)
           return $this->response_api(false, trans('messages.data_not_found'));

        $specialty->delete();
        return $this->response_api(true,  trans('messages.success'));
    }


}