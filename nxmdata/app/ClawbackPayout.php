<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClawbackPayout extends Model
{
    protected $table = "cm_clawback_payouts";
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'commission_payout_id',
        'amount_to_deduct',
        'amount_deducted',
    ];
}