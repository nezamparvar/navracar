<?php

return [
    // Staging defaults to blocking real outbound communication. Production
    // keeps the existing behavior unless explicitly configured otherwise.
    'disable_outbound' => (bool) env('NAVARACAR_DISABLE_OUTBOUND', false),
    // Address that receives lead-form and quote-request notification emails.
    'notify_email' => env('NAVARACAR_NOTIFY_EMAIL', 'nezamparvar@gmail.com'),

    'contact' => [
        'iran_phone' => '+98 912 051 2149',
        'uae_phone' => '+971 50 515 8484',
        'tehran_office' => '+98 21 8887 0878',
    ],
];
