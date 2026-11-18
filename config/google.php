<?php

use Google\Service\SearchConsole;

return
[

    'scopes' => [SearchConsole::WEBMASTERS],
    'access_type' => 'offline',
    'service' => [
        'enable' => env('GOOGLE_SERVICE_ENABLED', false),
        'file' => json_decode(env('GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION', ''), true),
    ],
    'config' => [
        'site_url' => env('GOOGLE_SEARCH_CONSOLE_SITE_URL'),
    ],
];
