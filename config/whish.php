<?php

return [
    'debug' => env('WHISH_DEBUG', false),
    'env' => env('WHISH_ENV', 'testing'), // 'live' or 'testing'

    'base_urls' => [
        'live' => 'https://whish.money/itel-service/api/',
        'testing' => 'https://lb.sandbox.whish.money/itel-service/api/',
    ],

    'channel' => env('WHISH_CHANNEL', ''),
    'secret' => env('WHISH_SECRET', ''),
    'website_url' => env('WHISH_WEBSITE_URL', env('APP_URL')),

    'timeout' => env('WHISH_TIMEOUT', 15),

    'default_currency' => env('WHISH_DEFAULT_CURRENCY', 'USD'),

    // Optional defaults for callbacks/redirects; override per-request as needed
    'default_success_callback_url' => env('WHISH_SUCCESS_CALLBACK_URL', env('APP_URL').'/api/whish/callback/success'),
    'default_failure_callback_url' => env('WHISH_FAILURE_CALLBACK_URL', env('APP_URL').'/api/whish/callback/failure'),
    'default_success_redirect_url' => env('WHISH_SUCCESS_REDIRECT_URL', env('APP_URL').'/payment/success'),
    'default_failure_redirect_url' => env('WHISH_FAILURE_REDIRECT_URL', env('APP_URL').'/payment/failure'),
];
