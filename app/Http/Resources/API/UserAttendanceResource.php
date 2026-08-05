<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use App\Services\AttendanceStatusService;

class UserAttendanceResource extends JsonResource
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
        $attendance_status = app(AttendanceStatusService::class)->resolve($this->resource, today());

        $attendance = $attendance_status['attendance'];

       $base = [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
            'whatsapp' => $this->whatsapp ?? '',
			'status'=>$this->status,
            'statusAsString'=>StatusEnum::toString($this->status),
			'position' => optional($this->userposition)->only(['id','ps_key','name']),
             'branches' => BranchSimpleResource::collection($this->whenLoaded('branches')),
             'departments' => UserBranchDepartmentResource::collection($this->whenLoaded('branchDepartments')),
             'attendance_status'=>[
                'value'=>$attendance_status['status']->value,
                'label'=>$attendance_status['status']->label(),
                'color'=>$attendance_status['status']->color(),
                'attendance_date'  => optional($attendance?->attendance_date)->format('Y-m-d'),
                'clock_in_time'    => optional($attendance?->clock_in)->format('H:i A') ?? '',
                'clock_out_time'   => optional($attendance?->clock_out)->format('H:i A') ?? '',
            ],


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