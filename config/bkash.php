<?php

return [
    "sandbox"         => env("BKASH_SANDBOX", true),
    "bkash_app_key"     => env("BKASH_APP_KEY", "XXXXXXXXXXXXXXX"),
    "bkash_app_secret" => env("BKASH_APP_SECRET", "XXXXXXXXXXXXXXXXXXXXX"),
    "bkash_username"      => env("BKASH_USERNAME", "XXXXXXXXXXXXXXXX"),
    "bkash_password"     => env("BKASH_PASSWORD", "XXXXXXXXXXXXXXXXXXXX"),
    "callbackURL"     => env("BKASH_CALLBACK_URL", "http://127.0.0.1:8000"),
    'timezone'        => 'Asia/Dhaka',
];
