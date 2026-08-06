<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'app_name' => $this->app_name,
            'app_logo' => $this->image,
            'android' => $this->android_build ?? [],
            'ios' => $this->ios_build ?? [],

          //  'map_key' => $this->map_key,
            'allow_distance' => $this->allow_distance,
            'phone' => $this->phone,
            'email' => $this->email,

            'shift_time_from' => $this->shift_time_from,
            'shift_time_to' => $this->shift_time_to,
            'weekly_off_days' => $this->weekly_off_days ?? [],
            'enable_visit_check_distance'=>$this->enable_visit_check_distance,
        ];
    }
}