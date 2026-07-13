<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Http\Requests\API\DepartmentRequest;
use App\Http\Resources\API\DepartmentResource;

class DepartmentController extends Controller
{
	public function index(Request $request)
	{
		$data = Department::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->withCount([ 'products','users'])
		               ->orderBy('created_at','DESC')->get();
        $data = DepartmentResource::collection($data);
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(DepartmentRequest $request)
    {
		\DB::beginTransaction();
      try {
			 $department = Department::updateOrCreate(['name'=>$request->name],$request->validated());
			 
			    if(!empty($request->branch_ids)){
					$department->branches()->sync($request->branch_ids);
				}

			 \DB::commit();
            return $this->response_api(true, trans('messages.success'),$department);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show($id)
    {
		$department = Department::find($id);
	   if(!$department)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),$department);
    }

	public function update(DepartmentRequest $request,$id) {
		\DB::beginTransaction();
      try {
		   $department = Department::find($id);
		   if(!$department)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$department->update($request->validated());

			if(!empty($request->branch_ids)){
			    $department->branches()->sync($request->branch_ids);
			}

			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$department);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy($id)
	{
		try {
			$department = Department::find($id);

			if (! $department) {
				return $this->response_api(false,trans('messages.data_not_found'));
			}

			$department->branches()->detach();
			$department->delete();

			return $this->response_api(true,trans('messages.success'));

		} catch (\Exception $e) {
			return $this->response_api(false,trans('messages.server_error'));
		}
	}


}