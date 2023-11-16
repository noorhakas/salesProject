<?php

namespace App\Http\Controllers\API\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ResetMail;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Validator;

class ForgetPasswordController extends Controller
{
     public function SendEmail(Request $request)
    {
        $check_mail = filter_var($request->email, FILTER_VALIDATE_EMAIL);
        $result = $this->sendCodeToEmail(["email"=>$check_mail]);
    }

    /**
     * sending code to email for forget password function
     * @param $data
     * @return JsonResponse
     */
    public function sendCodeToEmail($data)
    {
        $data['email'] = $data['email'];
        $validator = Validator::make($data, ['email' => 'required|exists:users,email,deleted_at,NULL']);

        if ($validator->fails())
            return $this->SendResponse(['status' => false,'message' => $validator->errors()->all()[0]], 402);
		
        $userCheckPasswordReset = \DB::table('password_resets')->where('email', $data['email'])->get();
        $user = User::where('email', $data['email'])->first();

        $token = $this->generateNumber();
        if (count($userCheckPasswordReset) > 0)
                \DB::table('password_resets')->where('email', $data['email'])->delete();

        $passwordReset = \DB::table('password_resets')->insert(['email' => $data['email'],
		                    'token' => \Hash::make($token),'created_at' => date('Y-m-d H:i:s')]);

        $user->update(["active_code"=>$token]);
        if (!$passwordReset)
            return $this->SendResponse(['status'=>false,'message'=>trans('messages.server_error')], 500);
    
        $user->notify(new ResetMail($user));
        return $this->SendResponse(['message' => trans('Backend.message-sent'), 'status' => true ,'data'=>new UserResource($user)], 200);
    }

    public function ResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), ['user_id' => 'required|exists:users,id', 'password' => 'required|min:6']);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all()[0], 'status' => false,], 402);
        }
		$user = User::find($request->user_id);
         User::where('id',$request->user_id)->update(['password'=> \Hash::make($request->password) ,'active_code' => NULL]);
        event(new PasswordReset($user));

        return response()->json(['message' => trans("passwords.reset"), 'status' => true], 200);
    }


	public static function generateNumber()
    {
        $number =str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        if(User::where('active_code', $number)->count()){
            $number = self::generateNumber();
        }
        return $number;
    }
}