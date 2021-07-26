<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IPayoutUser extends Model
{
    protected $table = 'cm_ipayout_users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'status',
        'username',
        'first_name',
        'last_name',
        'email',
        'date_of_birth',
        'company_name',
        'address_1',
        'address_2',
        'city',
        'state',
        'zip_code',
        'country_code',
        'website_password',
        'response',
        'reference_id',
    ];

    protected $attributes = [
        'response' => '{"none":""}',
        'status' => 'success',
    ];

}
