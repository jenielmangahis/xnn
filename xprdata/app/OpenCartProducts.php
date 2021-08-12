<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OpenCartProducts extends Model
{
    protected $table = 'oc_product';
    protected $primaryKey = 'product_id';
    public $timestamps = false;

}