<?php
/**
 * Created by PhpStorm.
 * User: Jeniel
 * Date: 10/12/2021
 * Time: 1:00 AM
 */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;


class CustomerAcquisitionBonus extends CommissionType
{
  

    public function count()
    {
        return $this->getTransactions()->count();
    }
    public function generateCommission($start, $length)
    {
        $orders = $this->getOrders($start, $length);

        foreach ($orders as $order) {
            $this->log("Processing Order ID " . $order['transaction_id']);
            $purchaser_id = $order['user_id'];
            $sponsor_id = $order['sponsor_id'];
            $product_id = +$order['shoppingcart_product_id'];

            $amount = $this->getBonus($product_id);

            if($amount > 0) {
                $this->insertPayout(
                    $sponsor_id,
                    $purchaser_id,
                    0,
                    100,
                    $amount,
                    "Sponsor ID: $sponsor_id received Fast Start Bonus from purchaser $purchaser_id",
                    $order['transaction_id'],
                    1,
                    $sponsor_id
                );
            }

            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

     private function getSponsoredCustomerOrders($start = null, $length = null)
    {
        $end_date = $this->getPeriodEndDate();
        $start_date = $this->getPeriodStartDate();

        $sql = "SELECT 
                t.transaction_id,
                t.user_id,
                t.sponsor_id,
                t.purchaser_catid,
                t.sponsor_catid,
                tp.shoppingcart_product_id
            FROM v_cm_transactions t
            JOIN cm_daily_ranks dr ON dr.user_id = t.sponsor_id AND dr.rank_date = '$end_date'
            JOIN transaction_products tp ON tp.transaction_id = t.transaction_id
            WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date' AND dr.is_active = 1
            AND FIND_IN_SET(tp.shoppingcart_product_id,'13,14,15')
            AND t.`type` = 'product' AND t.sponsor_catid = 13 -- for ambassadors catid only";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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