<?php
use Ramsey\Uuid\Uuid;

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
        'icon' => asset('theme/dist/img/applogo.png'),
    ];
    $fields['data'] = [
        'modelId' => isset($msgData['modelId']) && !empty($msgData['modelId']) ? $msgData['modelId'] : 0,
    ];
    return pushCurlCall($registrationId, $fields);
}

function sendPushAndroid($registrationId, $msgData)
{
	
    $fields['data'] = [
        // 'id' => isset($msgData['id']) && !empty($msgData['id']) ? $msgData['id'] : 0,
        'title' => $msgData['title'],
        'body' => $msgData['msg'],
        'sound' =>'default',
        'icon' => asset('theme/dist/img/applogo.png'),
        'modelId' => isset($msgData['modelId']) && !empty($msgData['modelId']) ? $msgData['modelId'] : 0,
		'topic'=>$msgData['topic']
    ];
//	dd($fields);
    return pushCurlCall($registrationId, $fields);
}

function pushCurlCall($registrationId, $fields)
{
    $url = 'https://fcm.googleapis.com/fcm/send';;//config('services.fcm.fcm_server_url');
	
	if($fields['data']['topic'] == 'user')
	{
		if (is_array($registrationId)) {
			$fields['registration_ids'] = $registrationId;
		} else {
			$fields['to'] = $registrationId;
		}
	}else{
		$fields['to'] = 'topics/' . $fields['data']['topic'];
	}
    
    $headers = [
        'Authorization: key=AAAAyaPsGb8:APA91bHwUFN9TruWwmZAy3GTqvXmceexz1mrnhN2qFPWu6MskoMhAgRJ9eSV9C98ZdKeZjRi6Nl_GfVXFFDGyYApxagtzghEvgnWCGX5zHAMvrsr0DspDErCTzTdXch25dJGT3CGSFtw',// . config('services.fcm.fcm_server_key'),
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


?>