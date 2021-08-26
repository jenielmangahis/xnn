<?php

namespace App\nxm\models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use App\flw\models\CommissionPeriods as CommissionPeriod;

class CommissionPeriodTypes extends  Eloquent
{
    protected $table = 'cm_commission_types';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function periods()
    {
        return $this->hasMany(CommissionPeriod::class, 'commission_type_id','id');
    }
}