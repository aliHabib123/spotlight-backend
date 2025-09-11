<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase services including FCM (Firebase Cloud Messaging)
    |
    */

    'project_id' => 'lebanon-spotlight',
    'credentials' => base_path('lebanon-spotlight-firebase-adminsdk-fbsvc-715d61d491.json'),

    'service_account' => [
        'path' => base_path('lebanon-spotlight-firebase-adminsdk-fbsvc-715d61d491.json'),
    ],

    'fcm' => [
        'enabled' => env('FCM_ENABLED', true),
        'default_title' => env('FCM_DEFAULT_TITLE', 'Lebanon Spotlight'),
        'new_spotlight_title' => env('FCM_NEW_SPOTLIGHT_TITLE', 'New Spotlight Available!'),
    ],
];
