<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BackgroundWorkerProcess extends Model
{
    const STATUS_RUNNING = 'RUNNING';
    const STATUS_PENDING = 'PENDING';
    const STATUS_DONE = 'DONE';
    const STATUS_FAILED = 'FAILED';
    const STATUS_CANCELLED = 'CANCELLED';

    const TYPE_PAYOUT = 'payout';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_CLAWBACK = 'clawback';
    const TYPE_CSV = 'csv';


    protected $table = "cm_background_worker_processes";

    protected $fillable = [
        'worker_id',
        'pid',
        'status',
        'order',
        'offset',
        'count',
        'type',
    ];

    public function worker()
    {
        return $this->belongsTo(BackgroundWorker::class, 'worker_id', 'id');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDone($query)
    {
        return $query->where('status', self::STATUS_DONE);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopePendingOrRunning($query)
    {
        return $query->where(function ($query) {
            $query->where('status', self::STATUS_PENDING)->orWhere('status', self::STATUS_RUNNING);
        });
    }
}
