<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 8/6/2019
 * Time: 8:42 AM
 */

namespace Commissions\CommissionTypes;

use App\CommissionPeriod;
use App\DailyVolume;
use App\Transaction;
use App\DailyRank;
use App\User;
use App\Rank;
use Commissions\Contracts\BackgroundWorkerLoggerInterface;
use Commissions\Contracts\CommissionTypeInterface;
use Commissions\Contracts\Repositories\PayoutRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PersonalSalesBonus extends CommissionType implements CommissionTypeInterface
{
    private $user_percentage = [];

    public function count()
    {
        return count($this->getTransaction());
    }

    public function generateCommission($start, $length)
    {
        $transactions = $this->getTransaction($start, $length);
        $member_label = config('commission.affiliate');
        foreach($transactions as $transaction)
        {

            $this->log('Evaluating order #'. $transaction->id);


            if($transaction->purchaser_catid == User::PRO_PLAN) // affiliate purchase
            {
                $payee_id = $transaction->userid;
            }
            else
            {
                if($transaction->sponsor_catid == User::PRO_PLAN)
                    $payee_id = $transaction->sponsorid;
                else
                    $payee_id = $this->findFirstUplineRepresentative($transaction->userid);

            }

            $commission_value = $transaction->cv;
            $end_date = $this->getPeriodEndDate();

            
            $payee_rank = null;
            $payee_volume = null;

            if(!$this->userHasPercentage($payee_id))
            {
                $payee_rank = DailyRank::ofMember($payee_id)
                    ->date($end_date)
                    ->first();


                if($payee_rank)
                {
                    $payee_volume = DailyVolume::find($payee_rank->volume_id);
                    $this->evaluateUserPercentage($payee_id, $payee_rank->paid_as_rank_id, +$payee_volume->prs);
                }
                else
                {
                    $this->setUserPercentage($payee_id, 0);
                }
            }

            $percentage = $this->getUserPercentage($payee_id);
            if($percentage == 0)
            {
                $this->log("$member_label $payee_id is not qualified for commission. ");
                continue;
            }


            $this->insertPayout(
                $payee_id,
                $transaction->userid,
                $commission_value,
                $percentage,
                $commission_value * ($percentage / 100),
                " $payee_id has earned a commission for order ". $transaction->id,
                $transaction->id,
                0,
                $transaction->sponsorid
            );

        }

    }

    public function getTransaction($start=null, $length=null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $t = Transaction::validTransactions()
            ->products()
            ->select('transactions.id'
                , 'transactions.userid'
                , 'transactions.sponsorid'
                , 'transactions.purchaser_catid'
                , 'transactions.sponsor_catid'
                , DB::raw('getCommissionValue(transactions.id) as cv')
                )
            ->whereBetween('transactiondate', [$start_date, $end_date])
            ->whereRaw('getCommissionValue(transactions.id) > 0');

        if($start !== null && $length !== null)
        {
            $t->skip($start)->take($length);
        }

        return $t->get();
    }

    public function evaluateUserPercentage($user_id, $paid_as_rank_id, $prs)
    {
        if(
            ($paid_as_rank_id >= Rank::REPRESENTATIVE
                && $paid_as_rank_id <=  Rank::EXEC_TEAM_LEADER)
            &&
            $prs >= 1000
        )
        {
            $percentage = 5;
        }
        elseif(
            $paid_as_rank_id >= Rank::MANAGER
            &&
            $prs >= 2000

        )
        {
            $percentage = 10;
        }
        else
        {
            $percentage = 0;
        }
        $this->setUserPercentage($user_id, $percentage);

    }

    public function getUserPercentage($user_id)
    {
        return $this->user_percentage[$user_id];
    }

    private function setUserPercentage($user_id, $percentage)
    {
        $this->user_percentage[$user_id] = $percentage;
    }

    private function userHasPercentage($user_id)
    {
        $user_ids = array_keys($this->user_percentage);

        if(count($user_ids) == 0) false;

        return in_array($user_id, $user_ids);
    }

    private function findFirstUplineRepresentative($customer_id){
        $q = $this->db->prepare(
            "WITH RECURSIVE upline AS (
                SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`,
                        active
                    FROM users
                       WHERE users.id = :customer_id
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        upline.`level` + 1 `level`,
                        p.active
                    FROM users p
                    INNER JOIN upline ON p.id = upline.parent_id AND p.levelid = 3
                    WHERE p.id <> upline.user_id
                )
                SELECT u.user_id FROM upline u JOIN cm_affiliates a 
                ON u.user_id = a.user_id
                WHERE u.active = 'Yes' AND u.level > 0
                ORDER BY u.level ASC
                LIMIT 1");
        $q->bindParam(':customer_id', $customer_id);
        $q->execute();

        return $q->fetchColumn();
    }
}