<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionGroupTypes extends Model
{
    protected $table = "cm_commission_group_types";
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
