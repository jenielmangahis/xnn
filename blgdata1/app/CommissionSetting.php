<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{

    protected $table = 'cm_commission_run_settings';
    protected $guarded = [];

    public function logs()
    {
        return $this->hasMany(CommissionSettingLog::class);
    }

    public function scopeLive($query)
    {
        return $query->where('is_live', 1);
    }

    public function commissionType()
    {
        return $this->belongsTo(CommissionType::class);
    }

    public function poolCommissionTypes()
    {
        return $this->commissionType()->where('name', 'LIKE', '%pool%');
    }

}
