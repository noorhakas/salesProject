<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\GlobalCollection;
use JsonSerializable;
use Carbon\Carbon;

class ProductNoteResource extends JsonResource
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

		 return [
            'id' => '#',
            'customer_name' => $this->customer_name,
			'note' =>$this->note ,
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
