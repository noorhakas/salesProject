<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Setting;
use App\Http\Resources\API\SettingResource;

class SettingController extends Controller
{
	 public function index(){
        $setting = Setting::first();
		 return $this->SendResponse(['status'=>true,'message'=>trans('messages.success'),'data'=>new SettingResource($setting)]);
	 }

	 public function store(Request $request){
         try {
			\DB::beginTransaction();
                  $setting = Setting::first();
				  if(!$setting)
				       $setting = (new Setting)::Create($request->all()); 
			      else
				        $setting->update($request->all());
					
				\DB::commit();
				return ['status'=>true,'message'=>trans('messages.success'),'data'=>new SettingResource($setting)];
			} catch (\Exception $e) {
				\DB::rollback();
				return ['status'=>false,'message'=>trans('messages.server_error')];
			}
	 }

}
