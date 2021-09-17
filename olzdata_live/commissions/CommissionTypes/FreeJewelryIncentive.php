<?php
/**
 * Created by PhpStorm.
 * User: Jason
 * Date: 8/6/2021
 * Time: 8:42 AM
 */

namespace Commissions\CommissionTypes;

use App\DailyVolume;
use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB;
use App\GiftCard as GC;

class FreeJewelryIncentive extends CommissionType implements CommissionTypeInterface
{
    const MINIMUM_PRS_REQUIREMENT = 3000;


    public function beforeCommissionRun()
    {
        GC::ofPeriod($this->getPeriodId())->delete();
    }

    public function count()
    {
        return count($this->getUsers());
    }

    public function generateCommission($start, $length)
    {
        // TODO: Implement generateCommission() method.
        $users = $this->getUsers($start, $length);
        $member_label = config('commission.affiliate');
        foreach($users as $user)
        {
            $this->log('Checking PRS of '. $member_label .'. PRS:'. $user->prs);

            $certificate_amount = 50;
            if($user->prs >= 6000)
            {
                $certificate_amount = 100;
            }

            $user_id = $user->user_id;
            $period_id = $this->getPeriodId();

            $this->addPayoutsGiftCards([
                    'user_id' => $user_id,
                    'sponsor_id'=> $user_id,
                    'commission_period_id'=>$period_id,
                    'amount'=>$certificate_amount,
                    'source'=>"Free Jewelry Incentive",
                    'rank_id'=> ''
                ]
            );

        }

    }

    public function getUsers($start=null, $length=null)
    {
        $end_date = $this->getPeriodEndDate();

        $u = DailyVolume::join('users as u', 'u.id', '=', 'cm_daily_volumes.user_id')
            ->join('cm_affiliates as c', 'c.user_id', '=', 'u.id')
            ->select('cm_daily_volumes.user_id', 'cm_daily_volumes.prs'
                , DB::raw("CONCAT_WS(' ', u.fname, u.lname) as member_name")
            )
        ->where('prs', '>=', self::MINIMUM_PRS_REQUIREMENT)
        ->where('cm_daily_volumes.volume_date', $end_date)
        ->where('u.active', 'Yes')
        ;

        if($start !== null && $length !== null)
        {
            $u->skip($start)->take($length);
        }

        return $u->get();
    }



    public function addPayoutsGiftCards($data)
    {
        DB::table('cm_gift_cards')->insert($data);
    }

    public function getDetails()
    {
        $period_id = $this->getPeriodId();

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
                CONCAT('ID ',u.id, ' will receive a ', p.amount, ' ', p.source) AS remarks"
            )
            ->where('p.commission_period_id', $period_id)
            ->get();
        return $sponsor_gift_cards;
    }

    public static function userIsQualified($user_id)
    {
        $u = DailyVolume::join('users as u', 'u.id', '=', 'cm_daily_volumes.user_id')
            ->join('cm_affiliates as c', 'c.user_id', '=', 'u.id')
            ->select('cm_daily_volumes.user_id', 'cm_daily_volumes.prs'
                , DB::raw("CONCAT_WS(' ', u.fname, u.lname) as member_name")
            )
            ->where('prs', '>=', self::MINIMUM_PRS_REQUIREMENT)
            ->whereRaw('cm_daily_volumes.volume_date = CURRENT_DATE()')
            ->where('u.active', 'Yes')
            ->where('u.id', $user_id)
            ->count();

        return ($u>0) ? true : false;

    }
}