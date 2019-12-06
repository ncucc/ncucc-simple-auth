<?php

return [
    'url' => env('SIMPLE_AUTH_URL', 'https://portal.ncu.edu.tw/system/name'),
    'client_id' => env('SIMPLE_AUTH_CLIENT_ID', 'client_id'),
    'client_secret' => env('SIMPLE_AUTH_CLIENT_SECRET', 'client_secret'),
    'token_url' => env('SIMPLE_AUTH_TOKEN_URL', 'https://portal.ncu.edu.tw/oauth2/token'),
    'userinfo_url' => env('SIMPLE_AUTH_USERINFO_URL', 'https://portal.ncu.edu.tw/apis/oauth/v1/info'),
];