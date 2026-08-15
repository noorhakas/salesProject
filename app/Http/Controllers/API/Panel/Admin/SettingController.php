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

			
			$data = $request->except(['image', '_method', '_token', 'file']);

			if ($request->has('enable_visit_check_distance')) {
				$data['enable_visit_check_distance'] = filter_var(
					$request->input('enable_visit_check_distance'),
					FILTER_VALIDATE_BOOLEAN
				);
			}

		
			if ($request->has('weekly_off_days')) {
				$days = $request->input('weekly_off_days');

				if (is_string($days)) {
					$decoded = json_decode($days, true);
					$days = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
						? $decoded
						: [];
				}

				$data['weekly_off_days'] = array_values(
					array_unique(
						array_map('intval', (array) $days)
					)
				);
			}

			$setting = Setting::first();

			if ($request->hasFile('image')) {
				$data['image'] = $request->file('image');
			}

			if (!$setting) {
				$setting = Setting::create($data);
			} else {
				$setting->update($data);
			}

			DB::commit();

			return ['status'=>true,'message'=>trans('messages.success'),'data'=>new SettingResource($setting)];
		} catch (\Exception $e) {
			DB::rollback();

			Log::error('Setting Store Error', [
				'message' => $e->getMessage(),
			]);

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