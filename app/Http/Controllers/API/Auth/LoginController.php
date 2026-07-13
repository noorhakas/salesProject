<?php

namespace App\Http\Controllers\API\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\LoginRequest;
use App\Http\Resources\API\UserResource;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
     protected function Authenticate(LoginRequest $request)
    {
		$field = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name';
		$credentials = [$field => $request->email, 'password' => $request->password];

		if(!Auth::attempt($credentials))
		    return $this->response_api(false,trans('messages.invalid_auth'));
	
		$user = $request->user();
        if (isset($user->status) && $user->status != 1) {
			return $this->response_api(false,trans('messages.suspended_account'));
        }

		// if(isset($request->DeviceType) &&  $request->DeviceType== 3 && $user->position != 1)
		//     return $this->response_api(false,trans('messages.invalid_auth'));

        if (Auth::guard()->attempt($credentials) && $user->status == 1) {
			if(isset($request->DeviceToken) && !empty($request->DeviceToken))
			    $user->update(['DeviceToken'=>$request->DeviceToken ,'DeviceType'=>$request->DeviceType]);

			return $this->response_api(true,trans('messages.success'),['accessToken' => $user->createToken('accessToken')->plainTextToken ,'user' =>new UserResource($user)]);
        }
       return $this->response_api(false,trans('messages.invalid_auth'));

    }

	public function Logout(Request $request)
	{
		 $request->user()->tokens()->delete();
		 return $this->response_api(true,trans('messages.user_logout'));

	}

}