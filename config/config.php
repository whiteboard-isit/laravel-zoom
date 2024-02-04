<?php

return [
    'api_key' => env('ZOOM_CLIENT_KEY'),
    'api_secret' => env('ZOOM_CLIENT_SECRET'),
    'base_url' => 'https://api.zoom.us/v2/',
    'account_id' => env('ZOOM_ACCOUNT_ID'),
    'authentication_method' => 'oauth2', // jwt and OAuth2 compatible
    'token_life' => 60 * 60 * 24 * 7, // In seconds, default 1 week
    'max_api_calls_per_request' => '5' // how many times can we hit the api to return results for an all() request
];
