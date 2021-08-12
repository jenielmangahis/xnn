<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class UserHistory extends Model
{
    const ENROLLER = 1;
    const PLACEMENT = 2;
    const MATRIX = 3;

    const MODULE_HOLDING_TANK = "Holding tank";
    const MODULE_EXPIRED_HOLDING_TANK = "Expired holding tank";

    protected $table = 'cm_genealogy_history';
}