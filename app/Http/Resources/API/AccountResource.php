<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'acc_type_id' => $this->acc_type_id,
            'acc_type'    => optional($this->accType)->name,
            'address'     => $this->address,
            'lat'         => $this->lat !== null ? (float) $this->lat : null,
            'lng'         => $this->lng !== null ? (float) $this->lng : null,
            'phone'       => $this->phone,
            'phone1'      => $this->phone1,
            'brick_id'    => $this->brick_id,
            'brick_name'  => optional($this->brick)->name,
            'class_id'    => $this->class_id,
            'class_name'  => optional($this->class)->name,
            'created_at'  => Carbon::parse($this->created_at)->toDayDateTimeString(),
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