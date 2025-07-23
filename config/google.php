<?php

return [
    'client_credentials' => env('GOOGLE_CLIENT_CREDENTIALS', storage_path('credentials/client_secret.json')),
    'redirect_uri'       => env('GOOGLE_REDIRECT_URI'),

    'scopes' => [
        \Google_Service_Calendar::CALENDAR_READONLY, // ← useなしで書く
    ],
];
