<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClawbackHistory extends Model
{
    protected $table = "cm_clawbacks_history";
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
