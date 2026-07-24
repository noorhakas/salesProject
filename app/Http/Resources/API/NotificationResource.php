<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        $extra = $this->resolveExtraData();

        return [
            'id'            => $this->id,
            'Uuid'          => $this->Uuid,
            'title'         => $this->vTitle,
            'body'          => $this->txBody,
            'is_read'       => (bool) $this->tiIsRead,
            'model_type'    => $this->model_type,
            'model_id'      => $this->model_id,
            'account_name'  => $extra['account_name'] ?? '',
            'customer_name' => $extra['customer_name'] ?? '',
            'visit_date'    => $extra['visit_date'] ?? '',
            'visit_time'    => $extra['visit_time'] ?? '',
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function resolveExtraData(): array
    {
        if (!in_array($this->model_type, ['visit', 'visit_request'])) {
            return [];
        }

        $visit = $this->notifiable; // من الـ morphTo relation

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
}