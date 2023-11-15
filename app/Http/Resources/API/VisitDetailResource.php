<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;

class VisitDetailResource extends JsonResource
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
       return [
            'id' => $this->id,
            'item_name' =>($this->type == 3) ? $this->file : $this->name,
			'count_of_sample'=>$this->count_of_sample ,
			'checked'=>$this->checked,
			'type'=>$this->type,
        ];
    }


   
}
