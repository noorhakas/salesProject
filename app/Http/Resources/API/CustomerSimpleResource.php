<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use App\Http\Resources\API\Concerns\FormatsIdName;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerSimpleResource extends JsonResource
{
    use FormatsIdName;

    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'image'              => $this->image,
            'address'     => $this->account?->address,
            'lat'         => $this->account?->lat !== null ? (float) $this->account?->lat : null,
            'lng'         => $this->account?->lng !== null ? (float) $this->account?->lng : null,
            'specialty'          => $this->idName($this->specialty),

        ];
    }

    public static function collection($resource)
    {
        return tap(new GlobalCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }
}