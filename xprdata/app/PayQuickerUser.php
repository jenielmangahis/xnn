<?php


namespace App;
use Illuminate\Database\Eloquent\Model;

class PayQuickerUser extends Model
{
    protected $primaryKey = 'user_id';
    protected $table = "cm_payquicker_users";
}