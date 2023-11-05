<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bricks;
use App\Http\Requests\API\BricksRequest;
use App\Http\Resources\API\BricksResource;

class BricksController extends Controller
{
	public function index(Request $request)
	{
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$bricks = (auth()->user()->access_all_data) ? Bricks::select('bricks.*') :  auth()->user()->bricks();
		$bricks = (clone $bricks)->when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','DESC')->paginate($limit);

		  $data = BricksResource::collection($bricks);			   
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function store(BricksRequest $request)
    {
		\DB::beginTransaction();
      try {
			 Bricks::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show($id)
    {
		$bricks = Bricks::find($id);
	   if(!$bricks)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),new BricksResource($bricks));
    }

	public function update(BricksRequest $request,$id) {
		\DB::beginTransaction();
      try {
		   $brick = Bricks::find($id);
		   if(!$brick)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$brick->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),$brick);
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy($id)
    {

	 try {	
		$bricks = Bricks::find($id);
		if(!$bricks)
           return $this->response_api(false, trans('messages.data_not_found'));

        $bricks->delete();
        return $this->response_api(true,  trans('messages.success'));
	 }catch (\Exception $e) {
			return $this->response_api(false, trans('messages.server_error'));
		}
    }


}