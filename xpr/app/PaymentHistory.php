<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_RUNNING = 'RUNNING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_HAS_ERROR = 'HAS_ERROR';

    protected $table = "cm_payment_history";

    protected $casts = [
        'download_links' => 'array',
    ];
    
    public function payments()
    {
        return $this->hasMany(Payment::class, 'history_id','id');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopePendingOrRunning($query)
    {
        return $query->whereIn('status', [self::STATUS_RUNNING, self::STATUS_PENDING]);
    }

    public function isRunning()
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }
}
