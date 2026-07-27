<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\Visit;
use App\Models\Notification;

class NotificationService
{
    public function sendNewPlanCreated(Plan $plan, User $creator): void
    {
        $this->send([
            'tokens'        => getUserFcmTokens(),
            'notify_title'  => 'new_plan',
            'notify_body'   => 'created_new_plan',
            'title'         => __('messages.new_plan'),
            'msg'           => __('messages.created_new_plan', ['vName' => $creator->name]),
            'notify_userId' => 0,
            'model_type'    => Plan::class,
            'tiDeviceType'  => 1,
            'notify_type'   => 1,
            'model_id'      => $plan->id,
        ]);
    }

    public function sendPlanReviewed(Plan $plan, User $owner, int $status, User $reviewer): void
    {
        $texts = [
            1 => [
                'notify_title' => 'accept_plan',
                'notify_body'  => 'manager_accept_plan',
                'title'        => __('messages.accept_plan'),
                'msg'          => __('messages.manager_accept_plan', ['vName' => $reviewer->name]),
            ],
            2 => [
                'notify_title' => 'reject_plan',
                'notify_body'  => 'manager_rejected_plan',
                'title'        => __('messages.reject_plan'),
                'msg'          => __('messages.manager_reject_plan', ['vName' => $reviewer->name]),
            ],
        ];

        $payload = $texts[$status] ?? null;
        if (!$payload) {
            return;
        }

        $this->send([
            'tokens'        => $owner->DeviceToken,
            'notify_title'  => $payload['notify_title'],
            'notify_body'   => $payload['notify_body'],
            'title'         => $payload['title'],
            'msg'           => $payload['msg'],
            'notify_userId' => $owner->id,
            'model_type'    => Plan::class,
            'tiDeviceType'  => 1,
            'notify_type'   => 1,
            'model_id'      => $plan->id,
        ]);
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
            $this->send([
                'tokens'        => $visit->DeviceToken,
                'notify_title'  => 'visit_request',
                'notify_body'   => 'visit_notification_body',
                'title'         => __('messages.visit_request'),
                'msg'           => __('messages.visit_request_msg', [
                    'userName'   => $planOwner->name,
                    'doctorName' => $visit->account_name . '-' . optional($visit->customer)->name,
                    'dateTime'   => $visit->visit_date . ' at ' . $visit->start_time,
                ]),
                'notify_userId' => $visit->id,
                'model_type'    => Visit::class,
                'tiDeviceType'  => 1,
                'notify_type'   => 1,
                'model_id'      => $visit->visit_id, // هنستخدمه بعدين مع notifiable() علشان نجيب الـ account/customer/date/time
            ]);
        }
    }

    public function sendNewVisitCreated(Visit $visit, User $creator): void
    {
        $this->send([
            'tokens'        => getUserFcmTokens(),
            'notify_title'  => 'new_visit',
            'notify_body'   => 'created_success_visit',
            'title'         => __('messages.new_visit'),
            'msg'           => __('messages.created_success_visit', ['vName' => $creator->name]),
            'notify_userId' => 0,
            'model_type'    => Visit::class,
            'tiDeviceType'  => 1,
            'notify_type'   => 1,
            'model_id'      => $visit->id,
        ]);
    }

    protected function send(array $data): void
    {
        (new Notification)->sendNotification($data);
    }
}