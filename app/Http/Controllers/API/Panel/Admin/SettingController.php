<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Http\Exports\SettingsReferenceExport;
use App\Http\Imports\SettingsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\API\SettingResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class SettingController extends Controller
{
	 public function index(){
        $setting = Setting::first();
		 return $this->SendResponse(['status'=>true,'message'=>trans('messages.success'),'data'=>new SettingResource($setting)]);
	 }

	 public function store(Request $request){
         try {
			DB::beginTransaction();
                  $setting = Setting::first();
				  if(!$setting)
				       $setting = (new Setting)::Create($request->all()); 
			      else
				        $setting->update($request->all());
					
				DB::commit();
				return ['status'=>true,'message'=>trans('messages.success'),'data'=>new SettingResource($setting)];
			} catch (\Exception $e) {
				DB::rollback();
				return ['status'=>false,'message'=>trans('messages.server_error')];
			}
	 }

	 public function exportSetting()
	{
		return Excel::download(new SettingsReferenceExport(), 'settings_reference.xlsx');
	}

	public function importSettings(Request $request)
	{
		$request->validate([
			'file' => 'required|file|mimes:xls,xlsx',
		]);

		try {
			$filePath = $request->file('file')->store('uploads');

			Excel::import(new SettingsImport(), $filePath);

			return $this->response_api(true, trans('messages.success'));

		} catch (\Exception $e) {
			Log::error('Settings Import Error', ['message' => $e->getMessage()]);

			return $this->response_api(false, trans('messages.server_error'));
		}
	}

}
