<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BackgroundWorker extends  Model
{
    protected $table = "cm_background_worker";

    const TYPE_RUN_COMMISSION = 'run_commission';
    const TYPE_RERUN_COMMISSION = 'rerun_commission';

    public function processes()
    {
        return $this->hasMany(BackgroundWorkerProcess::class, 'worker_id', 'id');
    }

    public function period()
    {
        return $this->belongsTo(CommissionPeriod::class, 'commission_period_id', 'id');
    }
}