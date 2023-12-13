<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;


class SettingResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
         
       return  [
            'id' => $this->id,
            'app_name' => $this->app_name,
			'app_logo'=>$this->image,
			'map_key'=>$this->map_key,
			'allow_distance'=>$this->allow_distance,
			'phone'=>$this->phone
        ];
    }
}
