<?php

return [
    'billing' => [
        'url' => env('CELESTIAL_BILLING_URL'),
        'token' => env('CELESTIAL_BILLING_TOKEN'),
    ],

    'payments' => [
        'url' => env('CELESTIAL_PAYMENTS_URL'),
        'token' => env('CELESTIAL_PAYMENTS_TOKEN'),
    ],
];
