<?php

return [
    // Version 1 accepts bearer tokens only; the website keeps its session login.
    'guard' => [],
    'stateful' => [],
    'routes' => false,
    'expiration' => max(1, (int) env('API_TOKEN_EXPIRATION_MINUTES', 1440)),
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
];
