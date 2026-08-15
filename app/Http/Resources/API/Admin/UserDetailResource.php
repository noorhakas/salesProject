<?php

namespace App\Http\Resources\API\Admin;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use App\Models\UserAccounts;
use App\Http\Resources\API\BranchSimpleResource;
use App\Http\Resources\API\UserBranchDepartmentResource;
use App\Http\Resources\API\UserShortDetailResource;


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

       $accounts_customers_ids = UserAccounts::where('user_id', $this->id)->get()->map(fn ($q) => $q->account_id . '_' . $q->customer_id);

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
             'branches' => BranchSimpleResource::collection($this->whenLoaded('branches')),
            'departments' => UserBranchDepartmentResource::collection($this->whenLoaded('branchDepartments')),
            'manager' => new UserShortDetailResource($this->whenLoaded('manager')),
            'position' => optional($this->userposition)->only(['id', 'ps_key', 'name']),

            'brick_ids' => $this->whenLoaded('bricks', fn () => $this->bricks->pluck('id'), fn () => $this->bricks()->pluck('id')),
            'product_ids' => $this->whenLoaded('products', fn () => $this->products->pluck('id'), fn () => $this->products()->pluck('id')),
            'department_ids' => $this->whenLoaded(
                'branchDepartments',
                fn () => $this->branchDepartments->pluck('department_id'),
                fn () => $this->branchDepartments()->pluck('department_id'),
            ),

            'customer_ids' => $accounts_customers_ids,
         //   'permissions' => $this->getAllPermissions()->pluck('name'),
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