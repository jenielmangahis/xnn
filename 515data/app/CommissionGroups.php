<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionGroups extends Model
{
    protected $table = "cm_commission_groups";
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
