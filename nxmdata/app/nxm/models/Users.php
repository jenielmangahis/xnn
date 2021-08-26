<?php

namespace App\nxm\models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Users extends Eloquent
{
    protected $table = 'users';
    public $timestamps = false;
}