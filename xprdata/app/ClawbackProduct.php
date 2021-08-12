<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClawbackProduct extends Model
{
    protected $table = "cm_clawback_products";
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'transaction_product_id',
        'quantity',
        'amount_to_deduct',
        'amount_to_deduct_price',
    ];
}