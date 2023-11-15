<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use App\Models\Customer;

class AccountResource extends JsonResource
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
         
      $base = [
            'id' => $this->id,
			'name' => $this->name,
			'acc_type_id'=>$this->acc_type_id,
			'acc_type'=>optional($this->accType)->name,
			'address'=>$this->address,
			'lat'=>$this->lat??'',
			'lng'=>$this->lng??'',
			'phone'=>$this->phone,
			'phone1'=>$this->phon1??'',
			'brick_id'=>$this->brick_id,
			'brick_name'=>optional($this->brick)->name,
			'class_id'=>$this->class_id,
			'class_name'=>optional($this->class)->name??'',
            'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),
        ];

		if(request()->route()->getName() == "accounts.show")
		{
			$base = array_merge($base,['doctor_list'=>$this->customers->map(fn($item)=>[
				'id'=>$item->id,
				'name'=>$item->name,
				'image'=>$item->image,
				'specialty_name'=>$item->specialty?->name,
				'work_days_AsString'=>($item->work_days ) ? collect(Customer::workDays())->whereIn('id',$item->work_days)->pluck('name') : [],
				'work_start_time'=>Carbon::parse($item->work_start_time)->format('H:i:s'),
				'work_end_time'=>Carbon::parse($item->work_end_time)->format('H:i:s'),
				'work_time'=>[Carbon::parse($item->work_start_time)->format('H:i:s'),Carbon::parse($item->work_end_time)->format('H:i:s')],

			])] );
		}
		return $base;
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
