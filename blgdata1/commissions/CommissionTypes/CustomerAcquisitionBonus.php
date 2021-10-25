<?php
/**
 * Created by 
 * User: Jeniel Mangahis
 * Date: 10/19/2021
 * Time: 10:00 PM
 */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;


class CustomerAcquisitionBonus extends CommissionType
{
  

    public function count()
    {
        return count($this->getSponsoredCustomerOrders());        
    }

    public function generateCommission($start, $length)
    {
        $orders = $this->getSponsoredCustomerOrders($start, $length);

        foreach ($orders as $order) {
            $this->log("Processing Order ID " . $order['transaction_id']);
            $purchaser_id = $order['user_id'];
            $sponsor_id   = $order['sponsor_id'];
            $is_valid     = true;
            if( $order['influencer_level'] == 1 ){
                $totalOrders = $this->totalOrdersLast60Days($order['sponsor_id']); //Has at least 1 sale in the last 60 days (if Free Influencer)
                if( $totalOrders['total_orders'] > 0 ){
                    $is_valid = true;
                }else{
                    $is_valid = false;
                }
            }
                
            if( $is_valid ){
                if($order['influencer_level'] > 0){                
                    $percentage = $this->getInfluencerCommission($order['influencer_level']);
                    $amount     = $this->computedInfluencerCommission($order['computed_cv'] ,$percentage);

                    if($amount > 0) {
                        $this->insertPayout(
                            $sponsor_id,
                            $purchaser_id,
                            $order['computed_cv'],
                            $percentage,
                            $amount,
                            "Sponsor ID: $sponsor_id received Customer Acquisition Bonus from purchaser $purchaser_id",
                            $order['transaction_id'],
                            1,
                            $sponsor_id
                        );
                    }
                }    

                $this->log(); // For progress bar. Put this every end of the loop.
            }           
        }

    }

    public function totalOrdersLast60Days( $sponsor_id )
    {   
        $end_date = $this->getPeriodEndDate();
        $last_60_days =  date('Y-m-d',(strtotime ( '-60 day' , strtotime ( $end_date) ) ));
        $today = date('Y-m-d');

        $sql = "
            SELECT COUNT(id) AS total_orders
            FROM v_cm_transactions t
            WHERE t.sponsor_id = '$sponsor_id' AND transaction_date >= '$last_60_days' AND transaction_date <= '$today'
        ";            

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }

    private function getSponsoredCustomerOrders($start = null, $length = null)
    {
        $end_date = $this->getPeriodEndDate();
        $start_date = $this->getPeriodStartDate();
        $customer = config('commission.member-types.customer');
        $sql = "SELECT 
                t.transaction_id,
                t.user_id,
                t.sponsor_id,
                t.purchaser_catid,
                t.sponsor_catid,
                dr.influencer_level,
                t.computed_cv
            FROM v_cm_transactions t
            JOIN cm_daily_ranks dr ON dr.user_id = t.sponsor_id AND dr.rank_date = '$end_date'
            WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date' 
                AND t.purchaser_catid = '$customer'
                AND (dr.rank_id >= 1 OR dr.influencer_level >= 1)
                AND dr.is_active = 1
                AND t.`type` = 'product' ";

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