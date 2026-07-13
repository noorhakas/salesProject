<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AttendanceStatusService;

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
            'attendance_status'=>[
                'value'=>$attendance_status['status']->value,
                'label'=>$attendance_status['status']->label(),
                'color'=>$attendance_status['status']->color(),
            ],
            'total_users'   => count($this->getAllSubordinateIds()),
        ];
    }
}
