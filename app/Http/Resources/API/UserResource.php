<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use Carbon\Carbon;

class UserResource extends JsonResource
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
            'user_name' => $this->user_name,
            'name' => $this->name,
            'email' => $this->email,
			'status'=>$this->status,
            'statusAsString'=>array_search($this->status,StatusEnum::getConstants()),
            'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),
			'role_id'=>$this->getRoleId(),
			'role_name'=>$this->getRoleName(),
			'access_all_data'=>$this->access_all_data,
        ];

		if(request()->route()->getName() == "users.show")
		{
			switch($this->access_all_data){
               case "1":
				$base = array_merge($base,['brick_ids'=>[] ,'product_ids'=> [],'customer_ids'=> []] );
			   break;
			   default:
			    $base = array_merge($base,['brick_ids'=>$this->bricks()->pluck('id') ,'product_ids'=> $this->products()->pluck('id')
			                ,'customer_ids'=> $this->customers()->pluck('id')] );
			   break;	
			}

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
