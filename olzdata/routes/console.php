<?php

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('nxm:test', function () {

})->describe('nxm testing');

Artisan::command('olz:rerun', function () {
    $begin = new \DateTime('2021-07-01');
    $end = new \DateTime('2021-07-13');

    $interval = new \DateInterval("P1D");
    $period = new \DatePeriod($begin, $interval, $end);

    foreach ($period as $dt) {
        $date = $dt->format("Y-m-d");
        $this->call("commission:run-volumes-and-ranks", ['date' => $date]);
    }

})->describe('rerun');

Artisan::command('php:info', function () {
    echo phpinfo();
})->describe('PHP Info');

