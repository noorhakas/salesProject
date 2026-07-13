<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Http\Resources\API\SettingResource;

class SettingController extends Controller
{
	 public function index(){
        $setting = Setting::first();
		 return $this->SendResponse(['status'=>true,'message'=>trans('messages.success'),'data'=>new SettingResource($setting)]);
	 }


}
