<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trial guest customer
    |--------------------------------------------------------------------------
    |
    | Guest device trials require a customer_id on licenses. All anonymous
    | trials are owned by this system customer (seeded, not for portal login).
    |
    */

    'trial_customer_email' => env('ULSP_TRIAL_CUSTOMER_EMAIL', 'trials@ulsp.local'),

];
