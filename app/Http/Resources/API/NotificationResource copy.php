<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;

class NotificationResource extends JsonResource
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
        return [
            'id' => $this->id,
            'title' => __('messages.' . $this->vTitle),
            'body' => ($this->model_type === "visit_request") 
                ? __('messages.' . $this->txBody, [
                    'userName' => $this->user->name,
                    'doctorName' => optional($this->account)->name . '-' . optional($this->customer)->name,
                    'dateTime' => Carbon::parse($this->visit_date)->format("Y-M-d") . ' at ' . Carbon::parse($this->start_time)->format("H:i a")
                ]) 
                : __('messages.' . $this->txBody, [
                    'vName' => optional($this->NotifyUser)->name
                ]),
            'model' => $this->model_type,
            'model_id' => $this->model_id,
            'tiIsRead' => $this->tiIsRead,
            'created_at' => Carbon::parse($this->created_at)->toDayDateTimeString(),
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
