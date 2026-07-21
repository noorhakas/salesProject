<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Customer;
use Carbon\Carbon;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'image'              => $this->image,
            'account_id'         => $this->account_id,
            'account'            => optional($this->account)->name,
            'brick_name'         => optional($this->account?->brick)->name,
            'acc_type_id'        => $this->acc_type_id,
            'class_id'           => $this->class_id,
            'acc_type'           => optional($this->accType)->name,
            'specialty_id'       => $this->specialty_id,
            'specialty_name'     => optional($this->specialty)->name ?? '',
            'class_name'         => optional($this->class)->name ?? '',
            'phone'              => (string) $this->phone,
            'phone1'             => (string) ($this->phone1 ?? ''),
           
            'created_at'         => Carbon::parse($this->created_at)->toDateTimeString(),
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