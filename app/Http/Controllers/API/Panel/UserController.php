<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\PlansResource;
use App\Http\Requests\API\UserRequest;
use App\Http\Requests\API\ProfileRequest;
use App\Models\User;

class UserController extends Controller
{
	public function index(Request $request)
	{
		if (!auth()->user()->hasPermissionTo('display Users'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$users = User::filter($request)->orderBy('created_at','DESC')->paginate($limit);
		   $data = UserResource::collection($users);
		return $this->response_api(true,trans('messages.success'),$data);
	}


	public function store(UserRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create User'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

        try {
			\DB::beginTransaction();
			  $acccess_all_data = $request->customer_select_all;
            $data = array_merge(['access_all_data'=>$acccess_all_data],$request->validated());
			$user = User::updateOrCreate(['user_name'=>$request->user_name],$data);
			$user->syncRoles($request->role_id);
 if($request->customer_select_all != 1){
			if(isset($request->brick_ids) && !empty($request->brick_ids))
			    $user->bricks()->sync($request->brick_ids);

			if(isset($request->product_ids) && !empty($request->product_ids))
			    $user->products()->sync($request->product_ids);

			if(isset($request->customer_ids) && !empty($request->customer_ids)){
				foreach($request->customer_ids as $key){
                   $keyData = explode('_',$key);
                   $account_customerIds[] = ['account_id'=>$keyData[0] , 'customer_id'=>$keyData[1] ];
				}
				 $user->customers()->sync($account_customerIds);
			}
}
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
		
		if (!auth()->user()->hasPermissionTo('update User'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);
      try {
		 \DB::beginTransaction();
		   if(!$user)
		      return $this->response_api(false, trans('messages.user_not_found'));
                        $acccess_all_data = $request->customer_select_all;
                        $data = array_merge(['access_all_data'=>$acccess_all_data],$request->validated());
			$user->update($data);
			$user->syncRoles($request->role_id);
if($request->customer_select_all != 1){
			if(isset($request->brick_ids) && !empty($request->brick_ids))
			    $user->bricks()->sync($request->brick_ids);

			if(isset($request->product_ids) && !empty($request->product_ids))
			    $user->products()->sync($request->product_ids);

			if(isset($request->customer_ids) && !empty($request->customer_ids)){
				foreach($request->customer_ids as $key){
                   $keyData = explode('_',$key);
                   $account_customerIds[] = ['account_id'=>$keyData[0] , 'customer_id'=>$keyData[1] ];
				}
				 $user->customers()->sync($account_customerIds);
			}
}else{
                $user->bricks()->delete();
                $user->products()->delete();
                $user->customers()->delete();

            }
			
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new UserResource($user));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy(User $user)
    {
       if (!auth()->user()->hasPermissionTo('delete User'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

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

	public function updateProfile(ProfileRequest $request){
		try {	
			$user = auth()->user();
			$user->update($request->validated());
			  return $this->response_api(true, trans('messages.success'),new UserResource($user));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}

	public function MycurrentPlan(){
		$current_plan = User::getCurrentPlan();
		$data = ($current_plan) ?new PlansResource($current_plan) : (object)[];
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function getPositionList(){
		$data = \App\Enums\UserPositionEnum::toArray();
		return $this->response_api(true,trans('messages.success'),$data);
	}

}