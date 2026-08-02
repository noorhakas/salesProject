<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address'=>$this->address,
            'phone' => $this->phone,
            'whatsapp'=>$this->whatsapp
        ];
    }
}