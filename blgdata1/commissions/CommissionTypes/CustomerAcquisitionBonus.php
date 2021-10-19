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
        return $this->getSponsoredCustomerOrders()->count();
    }
    public function generateCommission($start, $length)
    {
        $orders = $this->getSponsoredCustomerOrders($start, $length);

        foreach ($orders as $order) {
            $this->log("Processing Order ID " . $order['transaction_id']);
            $purchaser_id = $order['user_id'];
            $sponsor_id = $order['sponsor_id'];
            $product_id = +$order['shoppingcart_product_id'];
            $percentage = $this->getInfluencerCommission($order['influencer_level'])
            $amount = $this->computedInfluencerCommission($order['computed_cv'] ,$percentage);

            if($amount > 0) {
                $this->insertPayout(
                    $sponsor_id,
                    $purchaser_id,
                    0,
                    0,
                    $amount,
                    "Sponsor ID: $sponsor_id received Customer Acquisition Bonus from purchaser $purchaser_id",
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
                tp.shoppingcart_product_id,
                dr.influencer_level,
                t.computed_cv
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

    private function computedInfluencerCommission($amount,$percent)
    {  
        $computed = $amount * ( $percent/100 );
        return $computed;
    }

   
    private function getInfluencerCommission($influencer_id)
    {  
        $percentage = [
              1 => 10,
              2 => 15,
		      3 => 20
        ];

        return $percentage[$influencer_id];
    }
}