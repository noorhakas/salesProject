<?php
use Ramsey\Uuid\Uuid;
use App\Models\User;
use Carbon\Carbon;

function GetUuid()
{
    return Uuid::uuid6();
}

function __send_push($tiDeviceType, $vDeviceToken, $pushData)
{
    if (!empty($vDeviceToken)) {
        if ($tiDeviceType == 2) {
            sendPushIOS($vDeviceToken, $pushData);
        } else if ($tiDeviceType == 1) {
            sendPushAndroid($vDeviceToken, $pushData);
        }
    }
    return true;
}

function sendPushIOS($registrationId, $msgData)
{
    $fields['notification'] = [
        'title' => $msgData['title'],
        'body' => $msgData['msg'],
        'sound' => 'default',
        'icon' => asset('assets/img/royal-logo.png'),
    ];
    $fields['data'] = [
        'modelId' => isset($msgData['modelId']) && !empty($msgData['modelId']) ? $msgData['modelId'] : 0,
    ];
    return pushCurlCall($registrationId, $fields);
}

function sendPushAndroid($registrationId, $msgData)
{
	
    $fields['data'] = [
        'title' => $msgData['title'],
        'body' => $msgData['msg'],
        'sound' =>'default',
        'icon' => asset('assets/img/royal-logo.png'),
        'modelId' => isset($msgData['modelId']) && !empty($msgData['modelId']) ? $msgData['modelId'] : 0,
		'modelTye' => isset($msgData['modelType']) && !empty($msgData['modelType']) ? $msgData['modelType'] : 'notify',
		'created_at'=>Carbon::now()->toDateTimeString()
    ];
    return pushCurlCall($registrationId, $fields);
}

function pushCurlCall($registrationId, $fields)
{
		
	 $url = config('services.fcm.fcm_server_url');
    if (is_array($registrationId)) {
        $fields['registration_ids'] = $registrationId;
    } else {
        $fields['to'] = $registrationId;
    }

    $headers = [
        'Authorization: key=' . config('services.fcm.fcm_server_key'),
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disabling SSL Certificate support temporarly
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    $result = curl_exec($ch);
    curl_close($ch);
    Log::info(['notification payload19' => json_encode($fields)]);
    return ($result) ? 1 : 0;
}

 function getUserFcmTokens(){
   return  User::where(['position'=>1,'status'=>1])->whereNotNULL('DeviceToken')->pluck('DeviceToken')->toArray();

}


?>