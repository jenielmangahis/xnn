<?php

use App\User;
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

    require '/nxm/rep/code/office/php/sys/Encryption.php';
    $user = User::where('site', 'jen')->where('password', \Encryption::encrypt("jenjenjen"))->first();

    dd($user);

})->describe('nxm testing');

Artisan::command('nxm:pass {db}', function ($db) {

    require '/nxm/rep/code/office/php/sys/Encryption.php';

    try {
        $config = config("database.connections.mysql");
        $config['database'] = $db;

        $capsule = new \Illuminate\Database\Capsule\Manager;
        $capsule->addConnection($config);

        $users = $capsule->getConnection()->table("users")
            ->where("levelid", 1)
            ->whereIn("site", ['admin', 'ccadmin', 'ctadmin'])
            ->select("id", "site", "password")
            ->get()
            ->map(function ($user) {
                $user->password = \Encryption::encryptText($user->password, "decrypt");
                return (array)$user;
            });

        $this->table(["ID", "SITE", "PASSWORD"], $users->toArray());
    } catch (\Exception $ex) {
        $this->error("Mali man.");
    }

})->describe('Password');

Artisan::command('php:info', function () {
    echo phpinfo();
})->describe('PHP Info');

