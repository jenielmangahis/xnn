<?php

return [

    'ranks' => [
	'representative' => 1,
	'sr-representative' => 2,
	'leader' => 3,
    'team-leader' => 4,
    'sr-team-leader' => 5,
    'exec-team-leader' => 6,
    'manager' => 7,
    'sr-manager' => 8,
    'director' => 9

    ],

    'commission-types' => [
    'weekly-direct-profit' => 1,
	'personal-sales-commission' => 2,
	'fast-start' => 2,
    'free-jewelry-incentive' => 3,
    'monthly-level-commission' => 4,
    'sparkle-start-program' => 5,
    'silver-start-up' => 6
    ],

    'member-types' => [
        'customers' => '80362,15',
        'hostess' => '80362',
        'affiliates' => '13'
    ],

    'payment' => env('COMMISSION_PAYMENT'),

    'affiliate' => env('COMMISSION_AFFILIATE', 'Affiliate'),

    'first_day_of_the_week' => env('COMMISSION_FIRST_DAY_OF_THE_WEEK', 'monday'),  // monday | tuesday | wednesday | thursday | friday | saturday | sunday

    'payout_to_ledger_enable' => env('COMMISSION_PAYOUT_TO_LEDGER_ENABLE', false),

];
