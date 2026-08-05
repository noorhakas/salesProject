<?php
use Ramsey\Uuid\Uuid;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Setting AS SiteSetting;
use App\Enums\DayOffEnum;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;


function GetUuid()
{
    return Uuid::uuid6();
}


function __send_push($deviceType, $deviceToken, $data)
{
  //dd($deviceToken);
    if (empty($deviceToken)) return false;

    $payload = [
        'title' => $data['title'] ?? 'Notification',
        'body'  => $data['msg'] ?? '',
        'modelId' => (string) $data['model_id'] ?? 0,
        'modelType' => (string) $data['model_type'] ?? 'notify',
        'created_at' => Carbon::now()->toDateTimeString(),
    ];
  //dd($payload);
    if ($deviceType == 2) {
        return sendPushIOS($deviceToken, $payload);
    } else {
        return sendPushAndroid($deviceToken, $payload);
    }
}


function sendPushIOS($registrationId, $msgData)
{
    $message = [
        'message' => [
            'token' => $registrationId,
            'notification' => [
                'title' => $msgData['title'],
                'body'  => $msgData['body'],
            ],
            'data' => $msgData,
        ]
    ];

    return pushFCMv1($message);
}


function sendPushAndroid($registrationId, $msgData)
{
    $message = [
        'message' => [
            'token' => $registrationId,
            'data' => $msgData,
            'notification' => [
                'title' => $msgData['title'],
                'body'  => $msgData['body'],
            ],
        ]
    ];

    return pushFCMv1($message);
}


function pushFCMv1(array $message)
{
    $projectId = 'sales-rep-9a003'; 
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    // Get OAuth2 access token from service account JSON
    $client = new GoogleClient();
    $client->setAuthConfig(config('services.fcm.service_account'));
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $token = $client->fetchAccessTokenWithAssertion()['access_token'];

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    Log::info([
        'notification_payload' => $message,
        'fcm_response' => $result,
        'http_code' => $httpCode,
    ]);

    return ($httpCode == 200);
}

 function getUserFcmTokens(){
   return  User::where(['position'=>1,'status'=>1])->whereNotNULL('DeviceToken')->pluck('DeviceToken')->toArray();

}

    function setting(string $key = null, $default = null)
    {
        $settings = SiteSetting::first();


        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }

    function is_weekly_off_day(Carbon $date): bool
    {
        $offDays = setting('weekly_off_days', []);

        if (empty($offDays)) {
            return false;
        }

        $currentDay = DayOffEnum::fromCarbon($date->dayOfWeek)->value;

        return in_array($currentDay, array_map('intval', $offDays), true);
    }



?>