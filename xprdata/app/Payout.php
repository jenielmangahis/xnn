<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $table = "cm_commission_payouts";

    public $timestamps = false;
}
