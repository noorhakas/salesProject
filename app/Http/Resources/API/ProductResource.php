<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use App\Http\Resources\API\Concerns\FormatsIdName;



class ProductResource extends JsonResource
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
         
      $base =  [
            'id' => $this->id,
             'Uuid' => $this->Uuid,
            'name' => $this->name,
			'image'=>$this->image,
             'category' => $this->idName($this->category),
             'company' => $this->idName($this->company),
            'price'=>(float)$this->price,
			'description'=>$this->description,
             'status'=>$this->status,
            'statusAsString'=>StatusEnum::toString($this->status),
            'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),

        ];


	
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
