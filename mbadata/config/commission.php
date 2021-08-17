<?php

return [

    'ranks' => [
	'customer' => 1,
    'inactive-ibo' => 2,
	'ibo' => 3,
	'apprentice-trader' => 4,
	'junior-trader' => 5,
	'novice-trader' => 6,
	'qualified-trader' => 7,
	'team-trader' => 8,
	'national-trader' => 9,
	'international-trader' => 10,
	'world-trader' => 11,
	'global-trader' => 12,
    ],

    'commission-types' => [
	'fast-start-bonus' => 1,
    'car-bonus' => 2,
    'rank-incentives' => 4,
    'binary-commission' => 13,
    ],

    'member-types' => [
        'customers' => '13',
        'affiliates' => '14,15',
        'ibo' => '15',
        'ibo-autoship' => '14',
    ],

    'products' => [
        'smart_matrix_pack' => '148,151,154,157,160,163,166,169,181',
		'business_starter_pack' => '1,2',
		'consultant_business_pack_ids' => '73,79,82,112,115',
        'ibo_product_only' => '37',
        'gold_package' => '34',
        'platinum_package' => '31,34',
    ],

    'payment' => env('COMMISSION_PAYMENT'),

    'affiliate' => env('COMMISSION_AFFILIATE', 'IBO'),

    'first_day_of_the_week' => env('COMMISSION_FIRST_DAY_OF_THE_WEEK', 'monday'),  // monday | tuesday | wednesday | thursday | friday | saturday | sunday

    'payout_to_ledger_enable' => env('COMMISSION_PAYOUT_TO_LEDGER_ENABLE', true)

];
