<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Http\Requests\API\BranchRequest;

class BranchController extends Controller
{
	public function index(Request $request)
	{
		$data = Branch::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->get();

		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(BranchRequest $request)
    {
		\DB::beginTransaction();
      try {
			 $acclist = Branch::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$acclist);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show($id)
    {
		$branch = Branch::find($id);
	   if(!$branch)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),$branch);
    }

	public function update(BranchRequest $request,$id) {
		\DB::beginTransaction();
      try {
		   $branch = Branch::find($id);
		   if(!$branch)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$branch->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$branch);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy($id)
    {

	 try {	
		$branch = Branch::find($id);
		if(!$branch)
           return $this->response_api(false, trans('messages.data_not_found'));

        $branch->delete();
        return $this->response_api(true,  trans('messages.success'));
	 }catch (\Exception $e) {
			return $this->response_api(false, trans('messages.server_error'));
		}
    }


}