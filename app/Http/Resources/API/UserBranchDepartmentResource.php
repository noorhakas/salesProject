<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;


class UserBranchDepartmentResource extends JsonResource
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
            'branch'=>new BranchSimpleResource($this->whenLoaded('branch')),
        ];
    }
}
