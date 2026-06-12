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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'app_download' => [
        'ios_url' => env('IOS_APP_STORE_URL', 'https://apps.apple.com/search?term=NHM%20BDS'),
        'android_url' => env('ANDROID_PLAY_STORE_URL', 'https://play.google.com/store/search?q=NHM%20BDS&c=apps'),
        'fallback_url' => env('APP_DOWNLOAD_FALLBACK_URL', 'https://play.google.com/store/search?q=NHM%20BDS&c=apps'),
    ],

    // Expo Push Notifications (đẩy thông báo nền qua Expo, Expo tự relay sang FCM/APNs)
    'expo' => [
        // Access token tạo ở https://expo.dev (Account settings -> Access tokens).
        // Bắt buộc khi bật "Enhanced Security for Push Notifications" trên Expo.
        'access_token' => env('EXPO_ACCESS_TOKEN'),
        'push_url' => env('EXPO_PUSH_URL', 'https://exp.host/--/api/v2/push/send'),
    ],

    // hiện thị OTP trong response (mặc định: false)
    'otp_expose' => env('OTP_EXPOSE_IN_RESPONSE', false),
];
