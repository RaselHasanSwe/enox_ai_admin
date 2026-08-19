<?php

return [
    'api_url' => env('ENOX_API_URL', 'http://127.0.0.1:9000/api/v1'),
    'ws_url' => env('ENOX_WS_URL', 'ws://127.0.0.1:9000'),
    'product_cdn' => env('ENOX_PRODUCT_CDN', 'https://images.enorsia.com'),
    'display_timezone' => env('ENOX_DISPLAY_TIMEZONE', 'Europe/London'),
];
