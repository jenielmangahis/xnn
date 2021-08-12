<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $table = 'cm_ranks';

    public function scopeOfDefault($query)
    {
        return $query->where("id", 1);
    }
}
