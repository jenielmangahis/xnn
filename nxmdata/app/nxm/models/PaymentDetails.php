<?php

namespace App\nxm\models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

class PaymentDetails extends Eloquent
{
    protected $table = 'cm_payment_details';
    public $timestamps = false;

    public function getNewKey($payment_id) {

        $result = $this->selectRaw('MAX(`id`) AS `id`')->whereRaw('payment_id = ?', array($payment_id))->get()->toArray();
        if (isset($result[0]['id'])) {

            return (int)$result[0]['id'] + 1;
        } else {

            return 1;
        }
    }
}