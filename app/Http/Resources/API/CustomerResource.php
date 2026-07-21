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