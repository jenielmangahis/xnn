<?php

return [

    'ranks' => [
		'ambassador' => 1,
		'silver-influencer' => 2,
		'gold-influencer' => 3,
		'platinum-influencer' => 4,
		'sapphire-influencer' => 5,
		'pearl-influencer' => 6,
		'emerald-influencer' => 7,
		'ruby-influencer' => 8,
		'diamond-influencer' => 9,
		'double-diamond-influencer' => 10,
		'triple-diamond-influencer' => 11,
		'crown-diamond-influencer' => 12,
		'grace-diamond-influencer' => 13
    ],

    'commission-types' => [
        'fast-start-bonus' => 1,
        'fast-start-matching-bonus' => 2,
        'unilevel-team-commission' => 6,
        'unilevel-team-matching-bonus' => 7,
    ],

    'member-types' => [
        'customers' => '16',
        'affiliates' => '13,14',
        'influencer' => '14',
        'ambasador' => '13',
        'pro-free-plan' => '8033',
    ],

    'payment' => env('COMMISSION_PAYMENT'),

    'affiliate' => env('COMMISSION_AFFILIATE', 'Affiliate'),

    'first_day_of_the_week' => env('COMMISSION_FIRST_DAY_OF_THE_WEEK', 'monday'),  // monday | tuesday | wednesday | thursday | friday | saturday | sunday

    'payout_to_ledger_enable' => env('COMMISSION_PAYOUT_TO_LEDGER_ENABLE', false)

];