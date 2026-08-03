<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
                'id'   => $this->department->id,
                'name' => $this->department->name,
                'branch' => new BranchSimpleResource($this->whenLoaded('branch')),
        ];
    }
}