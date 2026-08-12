<?php

namespace App\Http\Resources\API\Admin;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\StatusEnum;
use App\Http\Resources\API\BranchSimpleResource;
use App\Http\Resources\API\UserBranchDepartmentResource;
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
            'emp_no' => $this->emp_no,
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
            'position' => optional($this->userposition)->only(['id', 'ps_key', 'name']),

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
