<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Clawback extends Model
{
    protected $table = "cm_clawbacks";
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'transaction_id',
        'is_per_product',
        'set_user_id',
        'cv',
        'percent',
        'amount',
        'is_full_order',
        'amount_to_deduct',
        'amount_to_deduct_price',
        'tax_amount',
        'shipping_amount',
        'refund_type',
        'product_amount',
    ];

    public function payouts()
    {
        return $this->hasMany(ClawbackPayout::class, 'clawback_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(ClawbackProduct::class, 'clawback_id', 'id');
    }

    public static function updateClawbackPayoutsByPeriod($period_id)
    {
        DB::table('cm_clawback_payouts')
            ->join(DB::raw("(
                    SELECT cpp.clawback_payout_id, SUM(cpp.amount_to_refund) AS amount_to_refund
                    FROM cm_clawback_pending_payouts cpp
                    JOIN cm_commission_payouts p ON p.id = cpp.commission_payout_id
                    JOIN cm_commission_periods pr ON pr.id = p.commission_period_id
                    WHERE pr.is_locked = 0
                    AND pr.id = :period_id
                    GROUP BY cpp.clawback_payout_id
                    FOR UPDATE
                ) cpp"), 'cpp.clawback_payout_id', '=', 'cm_clawback_payouts.id')
            ->addBinding(['period_id' => $period_id])
            ->update(['cm_clawback_payouts.amount_deducted' => DB::raw("IFNULL(cm_clawback_payouts.amount_deducted,0) + cpp.amount_to_refund")]);
    }

    public static function deleteClawbackPendingPayoutsByPeriod($period_id)
    {

        DB::transaction(function () use ($period_id) {

            $commissionPeriod = CommissionPeriod::findOrFail($period_id);

            $pendingPayoutsToClawback = DB::table("cm_clawback_pending_payouts")
                ->join("cm_commission_payouts AS p", 'cm_clawback_pending_payouts.commission_payout_id', '=', 'p.id')
                ->join('cm_commission_periods AS pr', 'pr.id', '=', 'p.commission_period_id')
                ->where('pr.is_locked', 0)
                ->where('pr.commission_type_id', $commissionPeriod->commission_type_id)
                ->select('p.id')
                ->lockForUpdate()
                ->get();

            $ids = $pendingPayoutsToClawback->pluck('id');

            if (!$ids) return;

            DB::table('cm_clawback_pending_payouts')
                ->whereIn('commission_payout_id', $ids)
                ->delete();

            DB::table('cm_commission_payouts')
                ->whereIn('id', $ids)
                ->delete();

        });

    }
}