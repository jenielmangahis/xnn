<?php

return [

    'ranks' => [
		'member' => 1,
		'consultant' => 2,
		'1-star' => 3,
		'2-star' => 4,
		'3-star' => 5,
		'4-star' => 6,
		'5-star' => 7,
		'6-star' => 8,
		'executive' => 9,
		'sapphire-executive' => 10,
		'ruby-executive' => 11,
		'emerald-executive' => 12,
		'diamond-executive' => 13,
		'crown-executive' => 14,
    ],

    'commission-types' => [
		'personal-sales-commission' => 1,
		'fast-start' => 2,
		'builder-bonus' => 3,
    ],

    'member-types' => [
        'customers' => '16',
        'affiliates' => '13,14'
    ],

	'products' => [
        'membership_products' => '160,163',
    ],

    'payment' => env('COMMISSION_PAYMENT'),

    'affiliate' => env('COMMISSION_AFFILIATE', 'Affiliate'),

    'first_day_of_the_week' => env('COMMISSION_FIRST_DAY_OF_THE_WEEK', 'monday'),  // monday | tuesday | wednesday | thursday | friday | saturday | sunday

    'payout_to_ledger_enable' => env('COMMISSION_PAYOUT_TO_LEDGER_ENABLE', false)

];
