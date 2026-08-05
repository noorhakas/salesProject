<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
	 'fcm' => [
		'fcm_server_url'=>'https://fcm.googleapis.com/fcm/send',
            
        'fcm_server_key' => 'AAAAJV_LA2A:APA91bEh2SHTWZ7ZiLanLJ8YhmVPjB7wL7tWUcJzqrSDqglFfsQtVeeBRNb_dBd8uefKjGjtGfdXR6ZE-ETky3aLCKrFZWN9_DKqT4f1D5JdaCRRir5qgGsdvNulw-xWen719oURmFR4',
        'service_account' => storage_path('app/firebase/firebase.json'),
        'project_id' =>'sales-rep-9a003'
    ],

];
