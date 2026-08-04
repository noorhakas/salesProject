<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Resources\API\NotificationResource;

class Notification extends Model
{
    use SoftDeletes;
    protected $table = 'notifications';

    protected $fillable = [
        'Uuid', 'user_id', 'tiNotificationType', 'vTitle', 'txBody',
        'tiIsRead', 'model_id', 'model_type', 'created_by', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function NotifyUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function notifiable()
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }

    public static function CreateNotify(array $data)
    {
        Notification::updateOrCreate(
            [
                'created_by' => auth()->user()->id ?? 0,
                'vTitle'     => $data['notify_title'],
                'model_id'   => $data['model_id'],
                'model_type' => $data['model_type'],
            ],
            [
                'Uuid'               => GetUuid(),
                'user_id'            => $data['notify_userId'],
                'tiNotificationType' => $data['notify_type'],
                'vTitle'             => $data['notify_title'],
                'txBody'             => $data['notify_body'],
                'tiIsRead'           => 0,
                'created_by'         => auth()->user()->id ?? 0,
                'model_id'           => $data['model_id'],
                'model_type'         => $data['model_type'],
                'payload'            => $data['payload'] ?? null,
            ]
        );
    }

    function notificationListing($request)
    {
        $authUser = auth()->user();
        $limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;

        $getNotificationQuery = Notification::leftJoin('users', 'users.id', '=', 'notifications.user_id')
            // جوين تاني على مين اللي عمل الأكشن (created_by) عشان نعرف مديره
            ->leftJoin('users as creators', 'creators.id', '=', 'notifications.created_by')
            ->where(function ($q) use ($authUser) {
                // 1) الإشعار موجّه ليا شخصيًا (زي: اتقبلت/اترفضت خطتك، أو طلب زيارة ليا)
                $q->where('notifications.user_id', $authUser->id)
                    // 2) أو إشعار عام للمديرين (user_id = 0) وأنا مدير اللي عمل الأكشن
                    ->orWhere(function ($q2) use ($authUser) {
                        $q2->where('notifications.user_id', 0)
                            ->where('creators.manager_id', $authUser->id);
                    });
            });

        $notificationList = (clone $getNotificationQuery)->select(['notifications.*'])
            ->orderBy('notifications.created_at', 'desc')
            ->paginate($limit);

        $UnReadNotify = (clone $getNotificationQuery)
            ->selectRaw('count(notifications.id) as notify_count')
            ->where('notifications.tiIsRead', 0)
            ->first();

        $countOfUnRead = $UnReadNotify ? $UnReadNotify->notify_count : 0;

        $notificationList->load('notifiable');

        $data = ['data' => NotificationResource::collection($notificationList), 'countOfUnRead' => $countOfUnRead];

        return ['status' => true, 'message' => trans('messages.success'), 'data' => $data];
    }

    public function notificationBadgeReset()
    {
        try {
            Notification::where(['tiIsRead' => 0])->update(['tiIsRead' => 1]);
            return ['status' => true, 'message' => trans('messages.success')];
        } catch (\Exception $e) {
            return ExceptionResponse($e);
        }
    }

    public function sendNotification(array $data)
    {
        self::CreateNotify([
            'created_by'    => auth()->user()->id ?? 0,
            'model_id'      => $data['model_id'],
            'model_type'    => $data['model_type'],
            'notify_userId' => $data['notify_userId'],
            'notify_type'   => $data['notify_type'],
            'notify_title'  => $data['notify_title'],
            'notify_body'   => $data['notify_body'],
            'payload'       => $data['payload'] ?? null,
        ]);

        $pushData = [
            'id'       => $data['model_id'],
            'title'    => $data['title'],
            'msg'      => $data['msg'],
            'sound'    => 'default',
            'model_id' => $data['model_id'],
            'model'    => $data['model_type'],
        ];

        __send_push($data['tiDeviceType'], $data['tokens'], $pushData);
    }
}