<?php

require_once __DIR__ . '/../config/commission.config.php';
require_once __DIR__ . '/../config/db.config.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => DB_HOST,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PASS,
    'charset'   => 'utf8',
    'collation' => 'utf8_general_ci',
    'prefix'    => '',
    'options'   => [
        \PDO::ATTR_EMULATE_PREPARES => true
    ]
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();