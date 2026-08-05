<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;


class PublicHolidayResource extends JsonResource
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
            'name' => $this->name,
            'date_from' =>Carbon::parse($this->date_from)->toDateString(),
            'date_to' =>Carbon::parse($this->date_to)->toDateString(),
             'active' => $this->active,
             'activeASString' =>$this->active ==  1 ? 'active' : 'Inactive',

			'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),
        ];
    }
}
