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
use App\Services\AttendanceStatusService;

class SalesRepProfileResource extends JsonResource
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
            'branches' => $this->branches->map->only(['id', 'name'])->values(),
            'departments' => $this->departments->map->only(['id', 'name'])->values(),
            'position' => optional($this->userposition)->only(['id','ps_key','name']),
            'supervisor_name'=>optional($this->manager)->name,
             'attendance_status'=>[
                'value'=>$attendance_status['status']->value,
                'label'=>$attendance_status['status']->label(),
                'color'=>$attendance_status['status']->color(),
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
