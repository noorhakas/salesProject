<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\Visit;
use App\Models\Notification;

class NotificationService
{
   
    protected function getPlanNotificationData(string $type, User $actor): array
    {
        return match ($type) {

            'created' => [
                'title_key' => 'new_plan',
                'body_key'  => 'created_new_plan',
                'type'      => 'plan',
                'data' => [
                    'vName' => $actor->name,
                ],
            ],

            'accepted' => [
                'title_key' => 'accept_plan',
                'body_key'  => 'manager_accept_plan',
                'type'      => 'plan',
                'data' => [
                    'vName' => $actor->name,
                ],
            ],

            'rejected' => [
                'title_key' => 'reject_plan',
                'body_key'  => 'manager_rejected_plan',
                'type'      => 'plan',
                'data' => [
                    'vName' => $actor->name,
                ],
            ],
        };
    }

    protected function getVisitNotificationData(string $type, array $params = []): array
    {
        return match ($type) {

            'created' => [
                'title_key' => 'new_visit',
                'body_key'  => 'created_success_visit',
                'type'      => 'visit',
                'data' => [
                    'vName' => $params['vName'] ?? '',
                ],
            ],

            'request' => [
                'title_key' => 'visit_request',
                'body_key'  => 'visit_request_msg',
                'type'      => 'visit',
                'data' => [
                    'userName'   => $params['userName']   ?? '',
                    'doctorName' => $params['doctorName'] ?? '',
                    'dateTime'   => $params['dateTime']   ?? '',
                ],
            ],
        };
    }

    public function sendNewPlanCreated(Plan $plan, User $creator): void
    {
        $notify = $this->getPlanNotificationData('created', $creator);

        $this->send([
            'tokens'        => getUserFcmTokens(),
            'notify_userId' => 0,
            'model_type'    => Plan::class,
            'model_id'      => $plan->id,
            'tiDeviceType'  => 1,
            'notify_type'   => 1,
        ], $notify);
    }

    public function sendPlanReviewed(Plan $plan, User $owner, int $status, User $reviewer): void
    {
        $type = match ($status) {
            1 => 'accepted',
            2 => 'rejected',
            default => null,
        };

        if (!$type) {
            return;
        }

        $notify = $this->getPlanNotificationData($type, $reviewer);

        $this->send([
            'tokens'        => $owner->DeviceToken,
            'notify_userId' => $owner->id,
            'model_type'    => Plan::class,
            'model_id'      => $plan->id,
            'tiDeviceType'  => 1,
            'notify_type'   => 1,
        ], $notify);
    }

    public function sendVisitRequests(Plan $plan, User $planOwner): void
    {
        $combinedVisits = $plan->visits()
            ->join('users', 'users.id', '=', 'visits.combine_with')
            ->leftJoin('accounts', 'accounts.id', '=', 'visits.account_id')
            ->selectRaw('users.id, users.DeviceToken, visits.id as visit_id, accounts.name as account_name, visits.customer_id, visits.visit_date, visits.start_time')
            ->where('visits.combine_with', '>', 0)
            ->get();

        foreach ($combinedVisits as $visit) {
            $notify = $this->getVisitNotificationData('request', [
                'userName'   => $planOwner->name,
                'doctorName' => $visit->account_name . '-' . optional($visit->customer)->name,
                'dateTime'   => $visit->visit_date . ' at ' . $visit->start_time,
            ]);

            $this->send([
                'tokens'        => $visit->DeviceToken,
                'notify_userId' => $visit->id,
                'model_type'    => Visit::class,
                // زي ما كانت في الكود الأصلي: visit_id مش id
                'model_id'      => $visit->visit_id,
                'tiDeviceType'  => 1,
                'notify_type'   => 1,
            ], $notify);
        }
    }

    public function sendNewVisitCreated(Visit $visit, User $creator): void
    {
        $notify = $this->getVisitNotificationData('created', [
            'vName' => $creator->name,
        ]);

        $this->send([
            'tokens'        => getUserFcmTokens(),
            'notify_userId' => 0,
            'model_type'    => Visit::class,
            'model_id'      => $visit->id,
            'tiDeviceType'  => 1,
            'notify_type'   => 1,
        ], $notify);
    }

    protected function send(array $meta, array $notify): void
    {
        (new Notification)->sendNotification(array_merge($meta, [
            'notify_title'  => $notify['title_key'],
            'notify_body'   => $notify['body_key'],
            'title'         => __('messages.' . $notify['title_key']),
            'msg'           => __('messages.' . $notify['body_key'], $notify['data']),
            'payload'       => $notify,
        ]));
    }
}