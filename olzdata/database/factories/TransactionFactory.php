<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\Transaction::class, function (Faker\Generator $faker) {
    $user_info = App\User::join('categorymap as c1', 'id', '=', 'c1.userid')
        ->join('categorymap as c2', 'sponsorid', '=', 'c2.userid')
        ->select(['id', 'sponsorid', 'fname', 'lname', 'c1.catid as purchaser_catid', 'c2.catid as sponsor_catid', 'modified'])
        ->where('c2.catid', config('commission.member-types.affiliates'))
        ->inRandomOrder()
        ->first();

    $transaction_date = \Carbon\Carbon::createFromTimestamp(strtotime($user_info->modified));
    $transaction_date->addDay(rand(1,3 ));

    return [
        'userid' => $user_info->id,
        'sponsorid' => $user_info->sponsorid,
        'status' => 'Approved',
        'billfname' => $user_info->fname,
        'billlname' => $user_info->lname,
        'purchaser_catid' => $user_info->purchaser_catid,
        'sponsor_catid' => $user_info->sponsor_catid,
        'ccnumber' => '5424000000000015',
        'transactiondate' => $transaction_date,
        'created_at' => $transaction_date,
        'is_test' => 1,
        'type' => 'product',
        'invoice' => $faker->randomLetter
    ];

});