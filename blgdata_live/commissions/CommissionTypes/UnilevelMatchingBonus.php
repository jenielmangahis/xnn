<?php
/**
 * Created by PhpStorm.
 * User: Jason
 * Date: 10/12/2021
 * Time: 1:00 AM
 */

namespace Commissions\CommissionTypes;

use App\CommissionPayout;
use App\CommissionPeriod;
use App\DailyRank;
use Illuminate\Support\Facades\DB as DB;


class UnilevelMatchingBonus extends CommissionType
{
    const MINIMUM_RANK = 8;

    public function count()
    {
        return $this->getUnilevelCommissionPayouts()->count();
    }
    public function generateCommission($start, $length)
    {
        $this->log('Getting payouts of unilevel team commissions');

        $payouts = $this->getUnilevelCommissionPayouts($start, $length);

        foreach($payouts as $payout) {
            if($payout->is_system_active == 0 || $payout->is_active == 0) continue;
            $percentage = $this->getPercentage($payout->paid_as_rank_id);
            if($percentage == 0)
            {
                $this->log('Minimum rank requirement is unmet');
                continue;
            }
            $amount = $payout->amount * ($percentage / 100);
            $payee_id = $payout->sponsorid;

            $remarks =" $payee_id has earned an unilevel team matching bonus of ".$amount." reference payout ". $payout->id;
            $this->log($remarks);

            $this->insertPayout(
                $payee_id,
                $payout->payee_id,
                $payout->amount,
                $percentage,
                $amount,
                $remarks,
                $payout->transaction_id,
                1,
                $payout->sponsorid
            );
        }
        return;
    }

    private function getPercentage($rank_id)
    {
        $percentage = 0;
        switch($rank_id)
        {
            case config('commission.ranks.emerald-influencer'):
            case config('commission.ranks.ruby-influencer'):
            case config('commission.ranks.diamond-influencer'):
                $percentage = 10;
                break;
            case config('commission.ranks.double-diamond-influencer'):
            case config('commission.ranks.triple-diamond-influencer'):
                $percentage = 15;
                break;
            case config('commission.ranks.crown-diamond-influencer'):
                $percentage = 20;
                break;
            case config('commission.ranks.grace-diamond-influencer'):
                $percentage = 30;
                break;
            default:
                $percentage = 0;
            break;

        }
        return $percentage;
    }

    /*
     * */

    private function getUnilevelCommissionPayouts($start=null, $length = null)
    {
        $commission_period = $this->getUnilevelTeamCommissionID();
        $end_date = $this->getPeriodEndDate();
        $payouts = DB::table('cm_commission_payouts as p')
            ->join('users as u', 'u.id', '=', 'p.payee_id')
            ->join('cm_daily_ranks as r', 'r.user_id', '=', 'u.sponsorid')
            ->where('p.commission_period_id', $commission_period)
            ->whereRaw("
                (
                    (CURRENT_DATE() < ? && r.rank_date = CURRENT_DATE())
                OR
                    (CURRENT_DATE() >= ? && r.rank_date = ?)
                )
                ", [$end_date, $end_date, $end_date])
            ->selectRaw(
                'r.is_active
                , r.is_system_active
                , r.paid_as_rank_id
                , p.id
                , p.transaction_id
                , p.payee_id
                , p.amount
                , u.sponsorid'
            )
        ;

        if($start!= null)
        {
            $payouts->skip($start)
                ->take($length);

        }


        return $payouts->get();

    }

    private function getUnilevelTeamCommissionID()
    {
        $start_date = $this->getPeriodStartDate();
        $unilevel_type_id = config('commission.commission-types.unilevel-team-commission');

        $commission_period_id = CommissionPeriod::where('start_date', $start_date)
            ->where('commission_type_id', $unilevel_type_id)
            ->first();
        ;

        return $commission_period_id->id;
    }
}