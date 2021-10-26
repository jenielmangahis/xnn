<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionSettingLog extends Model
{
    protected $table = 'cm_commission_run_setting_logs';
    protected $guarded = [];

    public function commissionSetting()
    {
        return $this->belongsTo(CommissionSetting::class);
    }
}
