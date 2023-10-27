<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;
use App\Enums\StatusEnum;


class BricksResource extends JsonResource
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
            'name' => $this->title,
			'status'=>$this->status,
			'statusAsString'=>StatusEnum::from($this->status)->toString(),
			'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),
        ];
    }
}
