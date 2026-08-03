<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AttendanceStatusService;
use App\Http\Resources\GlobalCollection;

class SupervisorSimpleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $attendance_status = app(AttendanceStatusService::class)->resolve($this->resource, today());

         return [
            'id'            => $this->id,
            'name'          => $this->name,
            'user_name'     => $this->user_name,
            'email'         => $this->email,
            'phone' => $this->phone ?? '',
            'whatsapp' => $this->whatsapp ?? '',
            'status'        => $this->status,
            'position' => optional($this->userposition)->only(['id','ps_key','name']),
           'branches' => BranchSimpleResource::collection($this->whenLoaded('branches')),
           'departments' => UserBranchDepartmentResource::collection($this->whenLoaded('branchDepartments')),
            'attendance_status'=>[
                'value'=>$attendance_status['status']->value,
                'label'=>$attendance_status['status']->label(),
                'color'=>$attendance_status['status']->color(),
            ],
            'total_users'   => count($this->getAllSubordinateIds()),
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
