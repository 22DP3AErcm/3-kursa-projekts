<?php
// filepath: /c:/Users/ercma/Documents/Trader/3-kursa-projekts/backend/config/cors.php
return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Options
    |--------------------------------------------------------------------------
    |
    | The allowed_origins, allowed_headers and allowed_methods options are
    | set to accept all by default. You can adjust them as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'register', 'login', 'csrf-token'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173', 'http://localhost:5174'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,

];