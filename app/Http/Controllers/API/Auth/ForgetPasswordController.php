<?php

namespace App\Http\Controllers\API\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ResetMail;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use App\Models\ResetCodePassword;
use Illuminate\Auth\Events\PasswordReset;
use Validator;

class ForgetPasswordController extends Controller
{
     public function SendEmail(Request $request)
    {
         $data = $request->validate([
            'email' => 'required|exists:users,email,deleted_at,NULL',
        ]);

	    ResetCodePassword::where('email', $request->email)->delete();		
        $user = User::where('email', $data['email'])->first();

        $data['token'] = $this->generateNumber();
        $codeData = ResetCodePassword::create($data);
        
        $user->notify(new ResetMail($user,$codeData->token));
        return $this->SendResponse(['message' => trans('messages.message_sent_successfully'), 'status' => true ,'data'=>['code'=>$data['token']]], 200);
    }

    /**
     * sending code to email for forget password function
     * @param $data
     * @return JsonResponse
     */
    public function checkOtpCode(Request $request)
    {
         $request->validate([
            'code' => 'required|string|exists:password_resets,token',
        ]);

        $passwordReset = ResetCodePassword::firstWhere('token', $request->code);

        // check if it does not expired: the time is one hour
        if ($passwordReset->created_at > now()->addHour()) {
            $passwordReset->delete();
            return response([ 'status' => false,'message' => trans('messages.code_is_expire')], 422);
        }

		return $this->SendResponse(['message' => trans('messages.success'), 'status' => true,'data'=>["code"=>$passwordReset->token] ], 200);
    }

    public function ResetPassword(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:password_resets,token',
            'password' => 'required|string|min:6',
        ]);

        $passwordReset = ResetCodePassword::firstWhere('token', $request->code);
        // check if it does not expired: the time is one hour
        if ($passwordReset->created_at > now()->addHour()) {
            ResetCodePassword::where('token', $request->code)->delete();
            return response([ 'status' => false ,'message' => trans('messages.code_is_expire')], 422);
        }

        $user = User::firstWhere('email', $passwordReset->email);
        $user->update($request->only('password'));

        ResetCodePassword::where('token', $request->code)->delete();
		return $this->SendResponse(['message' => 'password has been successfully reset', 'status' => true], 200);
    }


	public static function generateNumber()
    {
        $number =str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        return $number;
    }
}