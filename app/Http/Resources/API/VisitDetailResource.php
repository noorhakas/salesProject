<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Enums\VisitStatusEnum;

class VisitDetailResource extends JsonResource
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
        $statusData = VisitStatusEnum::find($this->status);

        return [
            'id' => $this->id,
            'customer' => new CustomerSimpleResource($this->whenLoaded('customer')),
            'account_id' => $this->account ? $this->account->id : 0,
            'account' => $this->account ? $this->account->name : '',
            'user' => new UserShortDetailResource($this->whenLoaded('user')),
            'combine_with_user' => new UserShortDetailResource($this->whenLoaded('doubleVisit')),
            'combine_with' => $this->combine_with ?? 0,
            'type' => ($this->type == 1) ? 'unplanned' : 'planned',
            'plan_code'       => optional($this->plan)->Uuid,
            'plan_status'     => optional($this->plan)->display_status,
            'status' => $this->status,
            'statusAsString' => $statusData['name'],
            'statusColor' => $statusData['color'],
            'visit_date' => Carbon::parse($this->visit_date)->toDateString(),
            'short_visit_date' => Carbon::parse($this->visit_date)->format('M-d'),
            'start_time' => Carbon::parse($this->start_time)->format('H:i:s'),
            'end_time' => Carbon::parse($this->end_time)->format('H:i:s'),
            'actual_start_time' => $this->actual_start_date ? Carbon::parse($this->actual_start_date)->format('Y-m-d H:i:s') : '',
            'actual_end_time' => $this->actual_end_date ? Carbon::parse($this->actual_end_date)->format('Y-m-d H:i:s') : '',
            'actual_visit_date' => $this->actual_start_date ? Carbon::parse($this->actual_start_date)->format('Y-m-d') : '',
            'actual_start_visit_time' => $this->actual_start_date ? Carbon::parse($this->actual_start_date)->format('H:i:s') : '',
            'actual_end_visit_time' => $this->actual_end_date ? Carbon::parse($this->actual_end_date)->format('H:i:s') : '',
            'notes' => (string) $this->notes,
            'user_location_lat' => (string) $this->user_location_lat,
            'user_location_lng' => (string) $this->user_location_lng,
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