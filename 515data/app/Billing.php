<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $table = "billing";
    protected $primaryKey = 'userid';
    public $timestamps = false;
}