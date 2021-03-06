<?php


namespace Commissions\Repositories;


use App\CommissionPayout;
use Commissions\Contracts\Repositories\PayoutRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PayoutRepository implements PayoutRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function insertPayout($period_id, $payee_id, $user_id, $commission_value, $percent, $amount, $remarks = '', $transaction_id = 0, $level = 0, $sponsor_id = 0)
    {
        $payout = new CommissionPayout();
        $payout->commission_period_id = $period_id;
        $payout->transaction_id = $transaction_id;
        $payout->user_id = $user_id;
        $payout->payee_id = $payee_id;
        $payout->level = $level;
        $payout->commission_value = $commission_value;
        $payout->percent = $percent;
        $payout->amount = $amount;
        $payout->remarks = $remarks;
        $payout->sponsor_id = $sponsor_id;

        $payout->save();
    }

    /**
     * @param int $period_id  The Commission Period ID
     * @return array
     */
    public function getSummary($period_id)
    {
        $summary = DB::table("cm_commission_payouts As cp")
            ->selectRaw("
                s.id As user_id,
                s.fname As first_name,
                s.lname As last_name,
                s.business As business_name,
                s.site As username,
                ROUND(SUM(cp.amount), 2) As total_payout
            ")
            ->leftJoin('users AS s', 's.id', '=', 'cp.payee_id')
            ->groupBy('s.id')
            ->where('cp.commission_period_id', $period_id)
            ->get();

        $sponsor_gift_cards_summary = DB::table("cm_gift_cards AS cp")
            ->selectRaw("
                s.id AS user_id,
                s.fname AS first_name,
                s.lname AS last_name,
                'N/A' AS business_name,
                s.site AS username,
                cp.amount AS total_payout
            ")
            ->leftJoin('users AS s', 's.id', '=', 'cp.sponsor_id')
            ->where('cp.commission_period_id', $period_id)
            ->get();

        $finalResult = $summary->merge($sponsor_gift_cards_summary);
        return $finalResult;
    }

    /**
     * @param int $period_id  The Commission Period ID
     * @return array
     */
    public function getDetails($period_id)
    {
        $details = DB::table('cm_commission_payouts As p')
            ->leftJoin('users AS u', 'u.id', '=', 'p.user_id')
            ->leftJoin('cm_commission_periods As pr', 'pr.id', '=', 'p.commission_period_id')
            ->leftJoin('cm_commission_types As t', 't.id', '=', 'pr.commission_type_id')
            ->select( 'p.payee_id As payee_id',
                'u.id As user_id',
                'u.fname As first_name',
                'u.lname As last_name',
                'p.sponsor_id As sponsor_id',
                't.name As commission_type',
                'p.commission_value As volume_calculated_from',
                'p.amount As payout',
                'p.level As level',
                'p.transaction_id As order_id',
                'p.percent As percent_payout' ,
                'p.remarks')
            ->where('p.commission_period_id', $period_id)
            ->orderByRaw('p.payee_id, p.level ASC')
            ->get();

        $sponsor_gift_cards = DB::table('cm_gift_cards As p')
            ->leftJoin('users AS u', 'u.id', '=', 'p.user_id')
            ->leftJoin('cm_commission_periods As pr', 'pr.id', '=', 'p.commission_period_id')
            ->leftJoin('cm_commission_types As t', 't.id', '=', 'pr.commission_type_id')
            ->selectRaw(
                "p.sponsor_id AS payee_id,
                u.id AS user_id,
                u.fname AS first_name,
                u.lname AS last_name,
                p.sponsor_id AS sponsor_id,
                t.name AS commission_type,
                p.amount AS payout,
                '' AS volume_calculated_from,
                '' AS order_id,
                '' AS percent_payout,
                CONCAT('ID ',u.id, ' Join Date: ',DATE(u.`created`),' | Payout Type: ', 'Gift Card') AS remarks"
            )
            ->where('p.commission_period_id', $period_id)
            ->get();
        $finalResult = $details->merge($sponsor_gift_cards);
        return $finalResult;
    }
}