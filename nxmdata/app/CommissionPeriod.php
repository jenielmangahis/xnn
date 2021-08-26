<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionPeriod extends Model
{
    protected $table = "cm_commission_periods";

    public function type()
    {
        return $this->belongsTo(CommissionType::class, 'commission_type_id', 'id');
    }

    public function scopeOfType($query, $commission_type_id)
    {
        return $query->where("commission_type_id", $commission_type_id);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', 1);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', 0);
    }
}