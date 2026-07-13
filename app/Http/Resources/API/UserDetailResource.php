<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use App\Enums\UserPositionEnum;
use App\Models\UserAccounts;
use Carbon\Carbon;

class UserDetailResource extends JsonResource
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
             'phone' => $this->phone ?? '',
            'whatsapp' => $this->whatsapp ?? '',
			'status'=>$this->status,
            'statusAsString'=>StatusEnum::toString($this->status),
            'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),
			'role_id'=>$this->getRoleId(),
			'role_name'=>$this->getRoleName(),
            'branches' => $this->branches->map->only(['id', 'name'])->values(),
            'departments' => $this->departments->map->only(['id', 'name'])->values(),
            'position' => $this->userposition->map->only(['id','ps_key', 'name'])->values(),
			'current_plan'=>!empty(self::getCurrentPlan())? new PlansResource(self::getCurrentPlan()) : (object)[],
			'access_all_data'=>$this->access_all_data,
			'DeviceToken'=>$this->DeviceToken,

        ];

		if(in_array(request()->route()->getName(), ["users.show" ,"users.profile"]))
		{
			switch($this->access_all_data){
               case "1":
				$base = array_merge($base,[
					'brick_ids'=>[] ,'product_ids'=> [],'department_ids'=>[],'customer_ids'=> [] ,'permissions'=>$this->getAllPermissions()->pluck('name')] );
			   break;
			   default:
			     $accounts_customers_ids = UserAccounts::where('user_id',$this->id)->get()->map(fn($q)=>$q->account_id.'_'.$q->customer_id);
			     $base = array_merge($base,[
					        'brick_ids'=>$this->bricks()->pluck('id') ,
							'product_ids'=> $this->products()->pluck('id'),
                            'department_ids'=>$this->departments()->pluck('id')
			                ,'customer_ids'=> $accounts_customers_ids
							,'permissions'=>$this->getAllPermissions()->pluck('name')
					]);
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
