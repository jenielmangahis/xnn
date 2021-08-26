<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $table = "cm_ledger";
    const TYPE_ADJUSTMENT = "adjustment";
    const TYPE_TRANSFER = "transfer";
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_WITHDRAWAL_REJECTED = 'withdrawal_reject';
    const TYPE_COMMISSION = 'commission';
    const TYPE_AUTOSHIP = 'autoship';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfMember($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }
}
