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

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    $sponsors = App\User::where('levelid', 3)
        ->whereRaw('id in (select userid from categorymap WHERE catid in ('.config('commission.member-types.affiliates').'))')
        ->pluck('id')->toArray();
    array_push($sponsors, 3);

    return [
        'sponsorid'=>$faker->randomElement($sponsors),
        'leaderid'=>$faker->randomDigitNotNull,
        'site'=>$faker->randomLetter,
        'password'=>$faker->password,
        'active'=>'Yes',
        'fname'=>'Commission ' . $faker->firstName,
        'lname'=>'Test',
        'address'=>$faker->address,
        'address2'=>$faker->secondaryAddress,
        'city'=>$faker->city,
        'state'=>$faker->state,
        'zip'=>$faker->postcode,
        'country'=>$faker->country,
        'email'=>$faker->unique()->safeEmail,
        'dayphone'=>$faker->phoneNumber,
        'evephone'=>$faker->phoneNumber,
        'fax'=>$faker->phoneNumber,
        'levelid'=>3,
        'timezone'=>$faker->timezone,
        'besttime'=>$faker->randomElement(['Anytime', 'Morning', 'Evening']),
        'business'=>$faker->company,
        'policy'=>$faker->randomElement(['Yes', 'No']),
        'cellphone'=>$faker->phoneNumber,
        'displayname'=>$faker->randomElement(['personal', 'both']),
        'newsletter'=>$faker->randomElement(['Yes', 'No']),
        'locked'=>Null,
        'locktime'=>Null,
        'memberid'=>$faker->randomDigit,
        'sponmemberid'=>$faker->randomDigit,
        'ip'=>$faker->ipv4,
        'optindatetime'=>$faker->dateTime,
        'displayphone'=>$faker->phoneNumber,
        'referurl'=>$faker->url,
    ];


});
