<?php

return [

    'ranks' => [
        'C0' => 0,
        'C1' => 1,
        'C2' => 2,
        'C3' => 3,
        'C4' => 4,
    ],

    'commission-types' => [
        'customer-profit' => 1,
        'level-bonus' => 2,
        'enroller-bonus' => 3,
    ],

    'member-types' => [
        'customers' => '15',
        'affiliates' => '13,14',
        'pro'   => 13,
        'premium'   => 14,
        'retail-customer'   => 15,
    ],

    'minimum-requirements' =>
    [
        'active_pv' => 100,
    ],

    'payment' => env('COMMISSION_PAYMENT'),

    'affiliate' => env('COMMISSION_AFFILIATE', 'Affiliate'),

    'first_day_of_the_week' => env('COMMISSION_FIRST_DAY_OF_THE_WEEK', 'monday'),  // monday | tuesday | wednesday | thursday | friday | saturday | sunday

    'payout_to_ledger_enable' => env('COMMISSION_PAYOUT_TO_LEDGER_ENABLE', false)

];