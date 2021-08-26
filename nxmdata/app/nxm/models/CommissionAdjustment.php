<?php

namespace App\nxm\models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\ctfo\models\CommissionPeriods as CommissionPeriod;

class CommissionAdjustment extends Eloquent
{
    protected $table = 'cm_commission_adjustments';
    public $timestamps = false;

    public function period()
    {
        return $this->belongsTo(CommissionPeriod::class, 'commission_period_id' , 'id');
    }
}