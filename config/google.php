<?php
return [
    // storage/credentials/client_secret.json への絶対パス
    'client_credentials' => env('GOOGLE_CLIENT_CREDENTIALS'),

    // OAuth リダイレクト URL
    'redirect_uri'       => env('GOOGLE_REDIRECT_URI'),
    'scopes' => [
        Google_Service_Calendar::CALENDAR_READONLY,
    ],

];
