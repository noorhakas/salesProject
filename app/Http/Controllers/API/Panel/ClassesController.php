<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Http\Requests\API\ClassesRequest;

class ClassesController extends Controller
{
	public function index(Request $request)
	{
		$data = Classes::select('id','name','frequency')->when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->get();
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(ClassesRequest $request)
    {
		\DB::beginTransaction();
      try {
			 $classes = Classes::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$classes);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show($id)
    {
		$classes = Classes::find($id);
	   if(!$classes)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),$classes);
    }

	public function update(ClassesRequest $request,$id) {
		\DB::beginTransaction();
      try {
		   $classes = Classes::find($id);
		   if(!$classes)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$classes->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$classes);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy($id)
    {

	 try {	
		$classes = Classes::find($id);
		if(!$classes)
           return $this->response_api(false, trans('messages.data_not_found'));

        $classes->delete();
        return $this->response_api(true,  trans('messages.success'));
	 }catch (\Exception $e) {
			return $this->response_api(false, trans('messages.server_error'));
		}
    }


}