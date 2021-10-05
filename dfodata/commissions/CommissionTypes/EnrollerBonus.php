<?php
/**
 * Written by Jason
 * 2021-09-30
 *
 * */

namespace Commissions\CommissionTypes;


use App\Affiliate;
use App\DailyVolume;
use Illuminate\Support\Facades\DB;

class EnrollerBonus extends CommissionType
{
    const FIRST_PURCHASE_PERCENTAGE = 100;
    const SUCCEEDING_PURCHASE_PERCENTAGE = 25;


    public function count()
    {
        return count($this->getTransaction());
    }
    public function generateCommission($start, $length)
    {
        $this->log('Getting Orders for enroller bonus');

        $transactions = $this->getTransaction($start, $length);

        foreach($transactions as $transaction)
        {
            $this->log('Evaluating order: '. $transaction->transaction_id);

            if($this->isQualifiedConsultant($transaction->sponsor_id) === false)
            {
                $this->log('No qualified upline found for transaction '. $transaction->transaction_id);
                continue;
            }

            $percentage = self::SUCCEEDING_PURCHASE_PERCENTAGE;
            if($this->hasPreviousTransaction($transaction->user_id) === false)
                $percentage = self::FIRST_PURCHASE_PERCENTAGE;

            $payee_id = $transaction->sponsor_id;

            $amount = $transaction->cv * ($percentage / 100);

            if($amount <= 0)
            {
                $this->log($transaction->transaction_id .' did not generate any commission. ');
                continue;
            }

            $remarks =" $payee_id has earned an enroller bonus of ".$amount." from order ". $transaction->transaction_id;
            $this->log($remarks);

            $this->insertPayout(
                $payee_id,
                $transaction->user_id,
                $transaction->cv,
                $percentage,
                $amount,
                $remarks,
                $transaction->transaction_id,
                0,
                $transaction->sponsor_id
            );


        }
    }

    public function getTransaction($start=null, $length=null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();
        $membership_products = "19,16";

        $t = DB::table('v_cm_transactions as t')->select(
                't.transaction_id',
                't.sponsor_id',
                't.user_id',
                't.computed_cv as cv',
                't.purchaser_catid',
                't.sponsor_catid'
            )
            ->where('t.computed_cv', '>', 0)
            ->whereRaw("DATE(transaction_date) BETWEEN ? AND ?", [$start_date, $end_date])
            ->whereRaw("EXISTS(SELECT 1 FROM transaction_products as tp WHERE tp.transaction_id = t.transaction_id AND FIND_IN_SET(tp.shoppingcart_product_id, '?'))", [$membership_products])

        ;


        $membership_products = "19,16";

        $query = "
            SELECT 
            t.transaction_id,
                t.sponsor_id,
                t.user_id,
                t.purchaser_catid,
                t.sponsor_catid,
                t.computed_cv as cv                       
             FROM v_cm_transactions t 
            JOIN transaction_products tp ON t.transaction_id = tp.transaction_id
            JOIN oc_product op ON  tp.shoppingcart_product_id = op.product_id 
            WHERE DATE(t.transaction_date) BETWEEN :start_date AND :end_date
            AND t.computed_cv > 0 
            AND EXISTS(SELECT 1 FROM transaction_products as tp WHERE tp.transaction_id = t.transaction_id AND FIND_IN_SET(tp.shoppingcart_product_id, :membership_products))
            GROUP BY t.transaction_id
        ";

        if($start !== null && $length !== null)
        {
            $query .= "LIMIT {$start}, {$length}";
        }


        $q = $this->db->prepare($query);
        $q->bindParam(':start_date', $start_date);
        $q->bindParam(':end_date', $end_date);
        $q->bindParam(':membership_products', $membership_products);
        $q->execute();

        return $q->fetchAll(\PDO::FETCH_OBJ);
    }

    public function hasPreviousTransaction($member_id)
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

    public function isQualifiedConsultant($consultant_id)
    {
        $rank_date = $this->getPeriodEndDate();
        $q =  DB::table('cm_affiliates as a')
            ->join('cm_daily_ranks as r', 'r.user_id', '=', 'a.user_id')
            ->where('r.rank_date', $rank_date)
            ->where('r.is_active', 1)
            ->where('r.is_system_active', 1)
            ->where('a.user_id', $consultant_id)
            ->get()
            ->count();

        return ($q>0)?true:false;
    }


}