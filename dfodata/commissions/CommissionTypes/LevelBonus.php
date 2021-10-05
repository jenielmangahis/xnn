<?php
/**
 * Written by Jason
 * 2021-09-30
 *
 * */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB;


class LevelBonus extends CommissionType
{
    public function count()
    {
        return count($this->getTransaction());
    }

    public function generateCommission($start, $length)
    {
        // TODO: Implement generateCommission() method.
        $this->log('Getting Orders for level bonus');
        $customer_catid = +config('commission.member-types.customers');

        $transactions = $this->getTransaction($start, $length);
        foreach ($transactions as $transaction) {
            $this->log('Evaluating order: ' . $transaction->transaction_id);
            $uplines = $this->getUplineConsultant($transaction->user_id);
            if(count($uplines) <= 0)
            {
                $this->log('No upline found');
                continue; // continue transaction loop
            }
            $level = 1;
            foreach($uplines as $upline)
            {
                $percentage = 0;
                if( +$upline->is_active == 0
                    OR +$upline->is_system_active == 0)
                {
                    $this->log('Upline #'. $upline->user_id .' is not qualified to earn level bonus from transaction #'.$transaction->transaction_id);
                    $level++;
                    continue; // next level
                }

                if (+$transaction->is_clothing == 0 && +$transaction->is_membership == 0)
                {
                    $this->log("The transaction #{$transaction->transaction_id} is neither clothing or membership category");
                    break;
                }

                if (+$transaction->is_clothing == 1)
                {
                    $percentage = $this->getClothingPercentage($level, $upline->paid_as_rank_id);
                }
                else
                {
                    if($this->hasPreviousTransaction($transaction->user_id) == false) // membership is earned on the second purchase
                    {
                        $this->log("The transaction #{$transaction->transaction_id} first membership category for {$transaction->user_id}");
                        break; // break upline loop next transaction
                    }

                    $percentage = $this->getMembershipPercentage($level, $upline->paid_as_rank_id);
                }


                if($percentage <= 0)
                {
                    $this->log('Upline #'. $upline->user_id .' has failed the rank requirement for level '.$level);
                    $level++;
                    continue;
                }
                $payee_id = $upline->user_id;
                $amount = $transaction->cv * ($percentage / 100);

                $remarks =" $payee_id has earned an level bonus of ".$amount." from order ". $transaction->transaction_id;
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


            } //end upline

        } // end transactions
    }

    private function getUplineConsultant($downline_id)
    {
        $rank_date = $this->getPeriodEndDate();
        $q = $this->db->prepare(
            "WITH RECURSIVE upline AS (
                SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`,
                        active
                    FROM users
                       WHERE users.id = :member_id
                       AND users.levelid = 3
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        upline.`level` + 1 `level`,
                        p.active
                    FROM users p
                    INNER JOIN upline ON p.id = upline.parent_id AND p.levelid = 3
                    WHERE p.id <> upline.user_id
                    AND p.levelid = 3
                )
                SELECT u.user_id
                    , r.paid_as_rank_id
                    , c.description
                    , r.is_active
                    , r.is_system_active
                    , u.level
                FROM upline u 
                JOIN cm_affiliates a
                    ON u.user_id = a.user_id
                JOIN cm_daily_ranks r 
                    ON r.user_id = a.user_id
                JOIN cm_ranks c 
                    ON r.paid_as_rank_id = c.id
                WHERE
                    r.rank_date = :rank_date             
                ORDER BY u.level ASC
                LIMIT 1,2"); // first consultant is considered as enroller

        $q->bindParam(':member_id', $downline_id);
        $q->bindParam(':rank_date', $rank_date);
        $q->execute();

        return $q->fetchAll(\PDO::FETCH_OBJ);
    }

    public function getTransaction($start=null, $length=null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();
        $membership_products = "19,16";

        $query = "
            SELECT 
            t.transaction_id,
                t.sponsor_id,
                t.user_id,
                t.purchaser_catid,
                t.sponsor_catid,
                t.computed_cv as cv,
              IF(FIND_IN_SET(tp.shoppingcart_product_id, :membership_product_category), 1, 0) as is_membership,
                    IF(!(FIND_IN_SET(tp.shoppingcart_product_id, :membership_product_category1) || op.is_giftcard = 1), 1, 0) as is_clothing
                       
             FROM v_cm_transactions t 
            JOIN transaction_products tp ON t.transaction_id = tp.transaction_id
            JOIN oc_product op ON  tp.shoppingcart_product_id = op.product_id 
            WHERE DATE(t.transaction_date) BETWEEN :start_date AND :end_date
            GROUP BY(t.transaction_id)
            
        ";

        if($start !== null && $length !== null)
        {
            $query .= "LIMIT {$start}, {$length}";
        }



        $q = $this->db->prepare($query);
        $q->bindParam(':start_date', $start_date);
        $q->bindParam(':end_date', $end_date);
        $q->bindParam(':membership_product_category', $membership_products);
        $q->bindParam(':membership_product_category1', $membership_products);
        $q->execute();

        return $q->fetchAll(\PDO::FETCH_OBJ);
    }

    private function getClothingPercentage($level, $rank_id)
    {
        $percentages =[
            1 => [1=>0, 2=>3, 3=>5, 4=>6],
            2 => [1=>0, 2=>0, 3=>0, 4=>3]
        ];

        return $percentages[$level][$rank_id];
    }

    private function getMembershipPercentage($level, $rank_id)
    {
        $percentages =[
            1 => [1=>0, 2=>10, 3=>10, 4=>10],
            2 => [1=>0, 2=>0, 3=> 0, 4=>10]
        ];

        return $percentages[$level][$rank_id];
    }


    private function hasPreviousTransaction($member_id)
    {
        $membership_products = "19,16";
        $start_date = $this->getPeriodStartDate();
        $t = DB::table('v_cm_transactions as t')
            ->where('t.computed_cv', '>', 0)
            ->where('t.user_id', $member_id)
            ->whereRaw("DATE(transaction_date) < ? ", [$start_date])
            ->whereRaw("EXISTS(SELECT 1 FROM transaction_products as tp WHERE tp.transaction_id = t.transaction_id AND FIND_IN_SET(tp.shoppingcart_product_id, '?'))", [$membership_products])
            ->get()
            ->count()
        ;

        return ($t>0)?true:false;
    }

}