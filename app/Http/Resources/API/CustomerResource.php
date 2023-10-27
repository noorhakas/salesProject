<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use Carbon\Carbon;

class CustomerResource extends JsonResource
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
			'image' => $this->image,
			'brick_id'=>$this->brick_id,
			'brick_name'=>optional($this->brick)->title,
			'acc_type_id'=>$this->acc_type_id,
			'acc_type'=>optional($this->accType)->name,
			'specialty_id'=>$this->specialty_id,
			'specialty_name'=>optional($this->specialty)->name,
			'class_id'=>$this->class_id,
			'class_name'=>optional($this->class)->name,
			'phone'=>$this->phone,
			'phone1'=>$this->phon1,
			'address'=>$this->address,
			'brief'=>$this->brief,
			'lat'=>$this->lat,
			'lng'=>$this->lng,
            'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),
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
