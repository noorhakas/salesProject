<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Customer;

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

       $base = [
            'id' => $this->id,
			'name' => $this->name,
			'image' => $this->image,
			'account_id' => $this->account_id,
			'account' => $this->account?->name,
			'brick_name'=>optional($this->account?->brick)->name,
			'acc_type_id'=>$this->acc_type_id,
			'acc_type'=>optional($this->accType)->name,
			'specialty_id'=>$this->specialty_id,
			'specialty_name'=>optional($this->specialty)->name??'',
			'class_name'=>optional($this->account?->class)->name??'',
			'phone'=>$this->phone,
			'phone1'=>$this->phone1??'',
			'address'=>optional($this->account)->address??'',
			'brief'=>$this->brief,
			'lat'=>optional($this->account)->lat??'',
			'lng'=>optional($this->account)->lng??'',
			'work_days_AsString'=>($this->work_days ) ? collect(Customer::workDays())->whereIn('id',$this->work_days)->pluck('name') : [],
			'work_days'=>$this->work_days ,
			'work_start_time'=>Carbon::parse($this->work_start_time)->format('H:i:s'),
			'work_end_time'=>Carbon::parse($this->work_end_time)->format('H:i:s'),
			'work_time'=>[Carbon::parse($this->work_start_time)->format('H:i:s'),Carbon::parse($this->work_end_time)->format('H:i:s')],
            'created_at'=>Carbon::parse($this->created_at)->toDateTimeString(),
        ];

		if(in_array(request()->route()->getName(), ["customers.show"]))
		{
              $base = array_merge($base,['recommended_medicines' => Product::where('specialty_id',$this->specialty_id)->get(['id','name'])]);
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
