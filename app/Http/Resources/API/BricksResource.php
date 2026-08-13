<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;
use App\Enums\StatusEnum;
use App\Http\Resources\API\Concerns\FormatsIdName;


class BricksResource extends JsonResource
{
    use FormatsIdName;
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
            'branch'    => $this->idName($this->branch),
			'created_at'=>Carbon::parse($this->created_at)->toDateTimeString(),
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
