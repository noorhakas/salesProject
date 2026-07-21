<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;

class AccountDetailResource extends AccountResource
{
    public function toArray($request)
    {
        return array_merge(parent::toArray($request), [
            'customer_list' => CustomerResource::collection($this->customers),
        ]);
    }
}