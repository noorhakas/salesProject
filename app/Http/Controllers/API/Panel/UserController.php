<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use App\Http\Resources\API\UserResource;
use App\Http\Requests\API\UserRequest;
use App\Models\User;

class UserController extends Controller
{
	public function index(Request $request)
	{
		$limit = $request->per_page??20;
		$users = User::filter($request)->orderBy('created_at','DESC')->paginate($limit);
		   $data = UserResource::collection($users);
		return $this->response_api(true,trans('messages.success'),$data);
	}


	public function store(UserRequest $request)
    {
		\DB::beginTransaction();
        try {
			$user = User::updateOrCreate(['user_name'=>$request->user_name],$request->validated());
			$user->syncRoles($request->role_id);

			if(isset($request->brick_ids) && !empty($request->brick_ids))
			    $user->bricks()->sync($request->brick_ids);

			if(isset($request->product_ids) && !empty($request->product_ids))
			    $user->products()->sync($request->product_ids);

			if(isset($request->customer_ids) && !empty($request->customer_ids))
			    $user->customers()->sync($request->customer_ids);

			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new UserResource($user));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }


	public function show(User $user)
    {
	   if(!$user)
           return $this->response_api(false, trans('messages.user_not_found'));

	   return $this->response_api(true, trans('messages.success'),new UserResource($user));
    }

	public function update(UserRequest $request,User $user) {
		\DB::beginTransaction();
      try {
		   if(!$user)
		      return $this->response_api(false, trans('messages.user_not_found'));

			$user->update($request->validated());
			$user->syncRoles($request->role_id);

			if(isset($request->brick_ids) && !empty($request->brick_ids))
			    $user->bricks()->sync($request->brick_ids);

			if(isset($request->product_ids) && !empty($request->product_ids))
			    $user->products()->sync($request->product_ids);

			if(isset($request->customer_ids) && !empty($request->customer_ids))
			    $user->customers()->sync($request->customer_ids);
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new UserResource($user));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy(User $user)
    {
		if(!$user)
           return $this->response_api(false, trans('messages.user_not_found'));

        $user->delete();
        return $this->response_api(true,  trans('messages.success'));
    }

	public function myProfile(Request $request){
		$user = $request->user();
		$data = new UserResource($user);
		return $this->response_api(true,trans('messages.success'),$data);
	}



	

}