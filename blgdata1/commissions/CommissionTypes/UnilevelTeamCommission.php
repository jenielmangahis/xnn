<?php
/**
 * Created by PhpStorm.
 * User: Jason
 * Date: 10/12/2021
 * Time: 1:00 AM
 */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;


class UnilevelTeamCommission extends CommissionType
{
    const MAX_LEVEL = 8;

    public function count()
    {
        return $this->getTransactions()->count();
    }
    public function generateCommission($start, $length)
    {
        $this->log('Getting Orders for unilevel team commissions');

        $transactions = $this->getTransactions($start, $length);

        foreach($transactions as $transaction)
        {
            $this->log('Generating commission for transaction #'. $transaction->transaction_id);
            $uplines = $this->getUplines($transaction->user_id);
            if(count($uplines)<=0)
            {
                $this->log('No upline found for ' . $transaction->user_id);
                continue; // move onto next transaction
            }
            $this->log('Number of uplines:' . count($uplines));
            $level = 1;
            foreach($uplines as $upline)
            {
                if($level > self::MAX_LEVEL) break;

                $this->log('Upline found ' . $upline->user_id);
                if( $upline->is_active == 0 || $upline->is_system_active == 0 )
                {
                    $this->log('Upline '. $upline->user_id .' is unqualified');
                    continue; // next upline // compression
                }

                $percentage = $this->getPercentage($upline->paid_as_rank_id, $level);
                if($percentage == 0)
                {
                    continue; // next upline
                }
                $payee_id = $upline->user_id;

                $amount = $transaction->cv * ($percentage / 100);

                $remarks =" $payee_id has earned an unilevel bonus of ".$amount." from order ". $transaction->transaction_id;
                $this->log($remarks);

                $this->insertPayout(
                    $payee_id,
                    $transaction->user_id,
                    $transaction->cv,
                    $percentage,
                    $amount,
                    $remarks,
                    $transaction->transaction_id,
                    $level,
                    $transaction->sponsor_id
                );

                $level++;

            }
        }

        return;
    }


    private function getUplines($downline_id)
    {
        $rank_date = $this->getPeriodEndDate();
        $q = $this->db->prepare(
            "WITH RECURSIVE upline AS (
                SELECT 
                        user_id,
                        sponsor_id AS parent_id,
                        1 AS `level`
                    FROM cm_genealogy_placement
                       WHERE user_id = :member_id
                       AND is_placed = 1 
                    UNION ALL
                    
                    SELECT
                        p.user_id,
                        p.sponsor_id AS parent_id,
                        upline.`level` + 1 `level`
                    FROM cm_genealogy_placement p
                    INNER JOIN upline ON p.user_id = upline.parent_id 
                    WHERE p.user_id <> upline.user_id
                   AND p.is_placed = 1
                )
                SELECT u.user_id
                    , r.paid_as_rank_id
                    , c.description
                    , r.is_active
                    , r.is_system_active
                    , u.level
                FROM upline u 
                JOIN cm_daily_ranks r 
                    ON r.user_id = u.user_id
                JOIN cm_ranks c 
                    ON r.paid_as_rank_id = c.id
                WHERE
                    r.rank_date = :rank_date             
                ORDER BY u.level ASC");

        $q->bindParam(':member_id', $downline_id);
        $q->bindParam(':rank_date', $rank_date);
        $q->execute();

        return $q->fetchAll(\PDO::FETCH_OBJ);

    }

    private function getTransactions($start = null, $length = null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $transactions = DB::table('v_cm_transactions as t')
            ->selectRaw('
                t.transaction_id,
                t.sponsor_id,
                t.user_id,
                t.purchaser_catid,
                t.sponsor_catid,
                t.computed_cv as cv
            ')
            ->whereBetween('t.transaction_date', [$start_date, $end_date]);

        if($start !== null)
        {
            $transactions->skip($start)->take($length);
        }
        return $transactions->get();

    }

    private function getPercentage($rank_id, $level = 1)
    {
        if($rank_id == config('commission.ranks.ambassador')) return 0;

        $percentage = [
            config('commission.ranks.silver-influencer')     => [2]
            , config('commission.ranks.gold-influencer')     => [2, 3]
		    , config('commission.ranks.platinum-influencer') => [2, 3, 4]
            , config('commission.ranks.sapphire-influencer') => [2, 3, 4, 5]
            , config('commission.ranks.pearl-influencer')    => [2, 3, 4, 5, 6]
            , config('commission.ranks.emerald-influencer')  => [2, 3, 4, 5, 6]
            , config('commission.ranks.ruby-influencer')     => [2, 3, 4, 5, 6, 7]
            , config('commission.ranks.diamond-influencer')  => [2, 3, 4, 5, 6, 7]
            , config('commission.ranks.double-diamond-influencer') => [2, 3, 4, 5, 6, 7, 8]
            , config('commission.ranks.triple-diamond-influencer') => [2, 3, 4, 5, 6, 7, 8]
            , config('commission.ranks.crown-diamond-influencer') => [2, 3, 4, 5, 6, 7, 8, 8]
            , config('commission.ranks.grace-diamond-influencer') => [2, 3, 4, 5, 6, 7, 8, 10]
        ];

        return $percentage[$rank_id][$level-1];
    }
}