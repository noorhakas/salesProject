<?php

namespace App\Http\Resources\API;

use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\GlobalCollection;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        $model = $this->notifiable;

        $params = $this->payload['data'] ?? [];


        return [
            'id'   => $this->id,
            'Uuid' => $this->Uuid,

            'title' => __('messages.' . $this->vTitle, $params),
            'body'  => __('messages.' . $this->txBody, $params),

            'employee' => $this->creator?->full_info,

            'is_read'    => (bool) $this->tiIsRead,
            'is_request' => $this->is_request,

            'model_type' => $this->model_type,
            'model_id'   => $this->model_id,

            // 'account_name'  => $extra['account_name'] ?? '',
            // 'customer_name' => $extra['customer_name'] ?? '',
            // 'visit_date'    => $extra['visit_date'] ?? '',
            // 'visit_time'    => $extra['visit_time'] ?? '',

            'created_at' => Carbon::parse($this->created_at)
                ->toDayDateTimeString(),

            'details' => $model?->getNotificationData()
        ];
    }

    protected function resolveExtraData(): array
    {
        if ($this->model_type !== Visit::class) {
            return [];
        }

        $visit = $this->notifiable;

        if (!$visit) {
            return [];
        }

        return [
            'account_name'  => optional($visit->account)->name,
            'customer_name' => optional($visit->customer)->name,
            'visit_date'    => $visit->visit_date,
            'visit_time'    => $visit->start_time,
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