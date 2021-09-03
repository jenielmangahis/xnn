<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RankIncentives extends Model
{
    protected $table = 'cm_rank_incentives';

    public $timestamps = false;

    public function scopeOfPeriod($query, $period_id)
    {
        return $query->where('commission_period_id', $period_id);
    }

    public static function deleteByPeriod($period_id)
    {
        return static::ofPeriod($period_id)->delete();
    }
}


