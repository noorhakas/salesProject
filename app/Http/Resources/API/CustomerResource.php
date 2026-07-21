<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use App\Http\Resources\API\Concerns\FormatsIdName;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Customer;
use Carbon\Carbon;

class CustomerResource extends JsonResource
{
    use FormatsIdName;

    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'image'              => $this->image,
            'account'            => $this->idName($this->account),
            'brick'              => $this->idName($this->account?->brick),
            'acc_type'           => $this->idName($this->accType),
            'specialty'          => $this->idName($this->specialty),
            'class'              => $this->idName($this->class),
            'phone'              => (string) $this->phone,
            'phone1'             => (string) ($this->phone1 ?? ''),
            'address'            => optional($this->account)->address ?? '',
            'brief'              => (string) $this->brief,
            'lat'                => optional($this->account)->lat,
            'lng'                => optional($this->account)->lng,
            'work_days_AsString' => $this->work_days
                ? collect(Customer::workDays())->whereIn('id', $this->work_days)->pluck('name')->values()
                : [],
            'work_days'          => (array) $this->work_days,
            'work_start_time'    => $this->work_start_time
                ? Carbon::parse($this->work_start_time)->format('H:i:s')
                : null,
            'work_end_time'      => $this->work_end_time
                ? Carbon::parse($this->work_end_time)->format('H:i:s')
                : null,
            'work_time'          => [
                $this->work_start_time ? Carbon::parse($this->work_start_time)->format('H:i:s') : null,
                $this->work_end_time ? Carbon::parse($this->work_end_time)->format('H:i:s') : null,
            ],
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