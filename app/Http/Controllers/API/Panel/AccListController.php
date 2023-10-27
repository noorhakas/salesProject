<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccList;
use App\Http\Requests\API\AccListRequest;

class AccListController extends Controller
{
	public function index(Request $request)
	{
		$data = AccList::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->get();

		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(AccListRequest $request)
    {
		\DB::beginTransaction();
      try {
			 $acclist = AccList::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$acclist);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show($id)
    {
		$acclist = AccList::find($id);
	   if(!$acclist)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),$acclist);
    }

	public function update(AccListRequest $request,$id) {
		\DB::beginTransaction();
      try {
		   $acclist = AccList::find($id);
		   if(!$acclist)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$acclist->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$acclist);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy($id)
    {

	 try {	
		$acclist = AccList::find($id);
		if(!$acclist)
           return $this->response_api(false, trans('messages.data_not_found'));

        $acclist->delete();
        return $this->response_api(true,  trans('messages.success'));
	 }catch (\Exception $e) {
			return $this->response_api(false, trans('messages.server_error'));
		}
    }


}