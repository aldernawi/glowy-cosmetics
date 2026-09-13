<?php

return [
    'cookie' => env('CART_COOKIE', 'glowy_cart'),
    'lifetime' => (int) env('CART_COOKIE_LIFETIME', 60 * 24 * 30),
    'secure' => env('CART_COOKIE_SECURE', env('APP_ENV') === 'production'),
];
