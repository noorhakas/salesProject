<?php

namespace App\Http\Resources\API\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;
use App\Http\Resources\GlobalCollection;



class DepartmentResource extends JsonResource
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
            'branch_ids'=>$this->branches()->pluck('id'),
             'sales_rep_count' => $this->users_count,
            'product_count' => $this->products_count,
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
