<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    protected $table = "cm_affiliates";
    protected $primaryKey = 'user_id';
}