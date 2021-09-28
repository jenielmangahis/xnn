<?php

return [

    'ranks' => [

    ],

    'commission-types' => [
        'customer-profit' => 1
    ],

    'member-types' => [
        'customers' => '19',
        'affiliates' => '13,15'
    ],

    'payment' => env('COMMISSION_PAYMENT'),

    'affiliate' => env('COMMISSION_AFFILIATE', 'Affiliate'),

    'first_day_of_the_week' => env('COMMISSION_FIRST_DAY_OF_THE_WEEK', 'monday'),  // monday | tuesday | wednesday | thursday | friday | saturday | sunday

    'payout_to_ledger_enable' => env('COMMISSION_PAYOUT_TO_LEDGER_ENABLE', false)

];