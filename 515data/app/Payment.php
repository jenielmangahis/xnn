<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    const STATUS_SUCCESS = "SUCCESS";
    const STATUS_FAILED = "FAILED";
    const STATUS_PROCESSING = "PROCESSING";
    const STATUS_FREEZE = "FREEZE";

    protected $table = 'cm_payments';

    public function details()
    {
        return $this->hasMany(PaymentDetails::class, 'payment_id','id');
    }
}
