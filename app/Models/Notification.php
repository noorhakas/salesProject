<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Resources\API\NotificationResource;

class Notification extends Model
{
	use SoftDeletes;
    protected $table = 'notifications';
	
	protected $fillable = ['Uuid','user_id' ,'tiNotificationType','vTitle','txBody','tiIsRead','model_id','model_type','created_by'];


	public function NotifyUser()
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
	public function user()
    {
        return $this->belongsTo(User::class);
    }
	public static function CreateNotify(array $data)
    {
		Notification::updateOrCreate(['created_by'=>auth()->user()->id??0 , 'model_id'=>$data['model_id'] ,'model_type'=>$data['model_type'] ],[
			'Uuid' => GetUuid(),
			'user_id' =>$data['notify_userId'],
			'tiNotificationType' => $data['notify_type'], // admins
			'vTitle' => $data['notify_title'],
			'txBody' => $data['notify_body'],
			'tiIsRead' => 0,
			'created_by'=>auth()->user()->id??0,
			'model_id'=>$data['model_id'],
			'model_type'=>$data['model_type']
		]);
	}

	function notificationListing($request){

		$limit = (is_numeric($request->per_page)) && ($request->per_page > 0) ? $request->per_page : 20;
				$getNotificationList = Notification::select(['notifications.*'])
				->leftJoin('users', 'users.id', '=', 'notifications.user_id')
				->where('notifications.user_id' ,auth()->user()->id)
				->when(auth()->user()->position != 3 ,fn($q,$v) =>
					$q->orWhere('notifications.tiNotificationType' , 1)
				)->paginate($limit);


		return ['status'=>true,'message'=>trans('messages.success'),'data'=>NotificationResource::collection($getNotificationList)];
	}

	public function notificationBadgeReset()
    {
        try {
            $user = auth()->user()->id;
            Notification::where(['user_id' => $user])->update(['tiIsRead' => 1]);
            return ['status'=>true,'message'=>trans('messages.success')];
        } catch (Exception $e) {
            return ExceptionResponse($e);
        }
    }

}