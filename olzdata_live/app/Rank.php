<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $table = 'cm_ranks';

    const REPRESENTATIVE     = 1;
    const SR_REPRESENTATIVE  = 2;
    const LEADER             = 3;
    const TEAM_LEADER        = 4;
    const SR_TEAM_LEADER     = 5;
    const EXEC_TEAM_LEADER   = 6;
    const MANAGER            = 7;
    const SR_MANAGER         = 8;
    const DIRECTOR           = 9;

    public function scopeOfDefault($query)
    {
        return $query->where("id", 1);
    }
}
