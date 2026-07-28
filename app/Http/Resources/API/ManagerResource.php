<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AttendanceStatusService;
use App\Http\Resources\GlobalCollection;


class ManagerResource extends JsonResource
{
    public function toArray($request)
    {
        $attendance = app(AttendanceStatusService::class)
            ->resolve($this->resource, today());

        $subordinateIds = $this->getAllSubordinateIds();

        $salesRepCount = \App\Models\User::whereIn('id', $subordinateIds)
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key', 'sales_rep')
            )
            ->count();

        $supervisorCount = \App\Models\User::whereIn('id', $subordinateIds)
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key', 'supervisor')
            )
            ->count();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_name' => $this->user_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,

            'position' => optional($this->userposition)->only([
                'id',
                'name',
                'ps_key',
            ]),

            'branches' => BranchSimpleResource::collection(
                $this->whenLoaded('branches')
            ),

            'departments' => DepartmentSimpleResource::collection(
                $this->whenLoaded('departments')
            ),

            'supervisors_count' => $supervisorCount,

            'sales_reps_count' => $salesRepCount,

            'attendance_status' => [
                'value' => $attendance['status']->value,
                'label' => $attendance['status']->label(),
                'color' => $attendance['status']->color(),
            ],
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