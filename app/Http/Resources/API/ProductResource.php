<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use Carbon\Carbon;

class ProductResource extends JsonResource
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
         
      $base =  [
            'id' => $this->id,
            'Uuid' => $this->Uuid,
            'name' => $this->name,
			'image'=>$this->image,
			'category_id'=>$this->category_id,
			'specialty_name'=>(string)optional($this->category)->name,
			'company_id'=>$this->company_id,
			'company_name'=>(string)optional($this->company)->name,
			'price'=>(float)$this->price,
			'description'=>$this->description,
			'files'=>$this->productfiles()->get(['id','file']),
            'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),

        ];

		if(in_array(request()->route()->getName(), ["products.show"]))
		{
              $base = array_merge($base,['similar_items' => self::where('category_id',$this->category_id)->get(['id','name','price','image'])]);
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
