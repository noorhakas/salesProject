<?php

namespace App\Http\Controllers\API\Panel\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notification;


class NotificationController extends Controller
{
	public function notificationListing(Request $request)
    {
		$model = new Notification();
		$response = $model->notificationListing($request);
		return $this->SendResponse($response);
	}

	public function notificationBadgeReset()
    {
        $model = new Notification();
        $response = $model->notificationBadgeReset();
        return $this->SendResponse($response);
    }

}
