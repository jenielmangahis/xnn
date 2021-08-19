<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommissionAdjustment extends Model
{
    protected $table = "cm_commission_adjustments";

    public static function generateAdjustmentByPeriod($period_id)
    {
        return DB::insert("
            INSERT INTO cm_commission_payouts (
                commission_period_id, 
                transaction_id, 
                user_id, 
                sponsor_id,
                payee_id,
                LEVEL,
                commission_value,
                percent,
                amount,
                remarks
            )
            SELECT
                a.commission_period_id,
                a.transaction_id,
                a.purchaser_id AS userid,
                a.user_id AS sponsor_id,
                a.user_id payee_id,
                a.`level`,
                a.amount AS cv,
                1, -- kay wala naga pangayog ug cv sa tool
                a.amount,
                CONCAT('Commission Adjustment: ', IFNULL(a.remarks, 'None'))
            FROM cm_commission_adjustments a
            JOIN cm_commission_periods pr ON pr.id = a.commission_period_id
            WHERE a.is_deleted = 0
                AND pr.is_locked = 0
                AND pr.id = ?
        ", [$period_id]);
    }
}
