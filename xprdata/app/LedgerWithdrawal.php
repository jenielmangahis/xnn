<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LedgerWithdrawal extends Model
{
    protected $table = "cm_ledger_withdrawal";

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_REJECTED = 'rejected';

}
