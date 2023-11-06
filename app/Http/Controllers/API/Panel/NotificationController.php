<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Http\Resources\API\PlansResource;
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
