<?php

namespace Commissions\CommissionTypes;

use App\CommissionPeriod;
use Commissions\BackgroundWorkerLogger;
use Commissions\Contracts\CommissionTypeInterface;
use Commissions\Repositories\PayoutRepository;
use Illuminate\Support\Facades\DB as DB;
use Commissions\QueryHelper;
use Carbon\Carbon;
use \PDO;
use DateTime;


class MonthlyCustomerProfit extends CommissionType implements CommissionTypeInterface
{
    protected $db;

    public function count()
    {
        return count($this->getQualifiedConsultants());
    }
    
    public function generateCommission($start, $length)
    {
        $consultants = $this->getQualifiedConsultants();
        foreach( $consultants as $c ){
            $sponsor_id = $c['sponsor_id'];
            $user_id    = $c['user_id'];

            $orders = $this->getOrders($sponsor_id, $start, $length);
            foreach ($orders as $key => $order) {
                $this->log("Processing customer profit for Order ID " . $order['order_id']);

                $user_id    = $order['user_id'];
                $sponsor_id = $order['sponsor_id'];
                $commission_value = $order['price'];
                $payee_id = $order['payee_id'];
                $order_id = $order['order_id'];
                $percentage = +$order['subscription_purchase'] === 1 ? 0.10 : 0.20; //if autoship/subscription purchase 10%
                $is_autoship_order = +$order['subscription_purchase'] === 1 ? "Yes" : "No";
                $order_type = $order['order_type'];
                $product_name = $order['product_name'];
                $computed_customer_profit = $order['computed_customer_profit'];

                if($commission_value > 0){
                    $this->insertPayout(
                        $payee_id,
                        $user_id,
                        $computed_customer_profit,
                        $percentage * 100,
                        $commission_value * $percentage,
                        "Order: $product_name | Order Type: $order_type | Autoship Order: $is_autoship_order",
                        $order_id,
                        $order['order_type'] === 'Customer Order' ? 1 : 0,
                        $sponsor_id
                    );
                }

                $this->log(); // For progress bar. Put this every end of the loop.
            }
        }

    }

    private function getQualifiedConsultants()
    {

        $start_date = $this->getPeriodStartDate();
        $end_date   = $this->getPeriodEndDate();

        $sql = "
            SELECT 
                t.transaction_id,
                t.sponsor_id,
                t.user_id
            FROM v_cm_transactions t  
            JOIN users u ON t.sponsor_id = u.id
            JOIN cm_daily_volumes cdv ON t.sponsor_id = cdv.user_id
            JOIN cm_daily_ranks cdr ON cdr.volume_id = cdv.id 
            WHERE 
                t.purchaser_catid IN('13,14,15') AND 
                t.is_autoship = 1 
                AND 
                    t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND 
                EXISTS (
                    SELECT 1
                    FROM oc_autoship oa 
                    WHERE oa.trans_id = t.transaction_id
                )
                AND 
                (
                    SELECT SUM(dva.pv) 
                    FROM cm_daily_volumes dva 
                    WHERE dva.user_id = t.sponsor_id
                ) >= 100
                AND u.active = 'Yes'
                AND cdr.rank_id >= 1
                AND cdr.rank_date = '$end_date'
                AND cdr.is_system_active = 1
            GROUP BY t.sponsor_id
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    private function getOrders($sponsor_id, $start = null, $length = null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.retail-customers');
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $sql = "SELECT 
                    t.user_id,
                    t.sponsor_id,
                    t.sponsor_id AS payee_id,
                    (tp.price * tp.quantity) + IFNULL((SELECT och.amount FROM oc_coupon_history och WHERE och.order_id = tp.transaction_id), 0) AS price,
                    op.`model` AS product_name,
                    op.is_autoship AS subscription_purchase,
                    t.transaction_id AS order_id,
                    tp.computed_customer_profit,
                    'Customer Order' AS order_type
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
                JOIN users s ON s.id = t.sponsor_id
                JOIN transaction_products tp ON tp.transaction_id = t.transaction_id
                JOIN oc_product op ON op.`product_id` = tp.`shoppingcart_product_id`
                WHERE t.type = 'product' 
                    AND t.transaction_date BETWEEN '$start_date' AND '$end_date' 
                    AND t.sponsor_id = '$sponsor_id' 
                    AND tp.shoppingcart_product_id NOT IN(19,16)
                    AND t.is_gift_card = 0
                    AND FIND_IN_SET(t.purchaser_catid,'$customers')
                    AND FIND_IN_SET(t.sponsor_catid,'$affiliates')
                    -- AND u.active = 'Yes'
                    AND op.`commission_value` > 0
                
                UNION ALL
                
                SELECT 
                    t.user_id,
                    t.sponsor_id,
                    t.user_id AS payee_id,
                    (tp.price * tp.quantity) + IFNULL((SELECT och.amount FROM oc_coupon_history och WHERE och.order_id = tp.transaction_id), 0) AS price,
                    op.`model` AS product_name,
                    op.is_autoship AS subscription_purchase,
                    t.transaction_id AS order_id,
                    tp.computed_customer_profit,
                    'Personal Order' AS order_type
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
                JOIN users s ON s.id = t.sponsor_id
                JOIN transaction_products tp ON tp.transaction_id = t.transaction_id
                JOIN oc_product op ON op.`product_id` = tp.`shoppingcart_product_id`
                WHERE t.type = 'product' 
                    AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                    AND t.sponsor_id = '$sponsor_id' 
                    AND tp.shoppingcart_product_id NOT IN(19,16)
                    AND t.is_gift_card = 0
                    -- AND u.active = 'Yes'
                    AND FIND_IN_SET(t.purchaser_catid,'$affiliates')
                    AND op.`commission_value` > 0";

        $sql = "
            SELECT
                a.*
            FROM (
                $sql
            ) a
            WHERE " . QueryHelper::NotExistsUnderBen('a.payee_id') . "
            ORDER BY a.order_id, a.payee_id, a.order_type
        ";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function setDates($end_date = null)
    {
        $end_date = $this->getRealCarbonDateParameter($end_date);

        $this->end_date = $end_date->format("Y-m-d");
        $this->start_date = $end_date->copy()->firstOfMonth()->format("Y-m-d");
    }

    public function run($comissions_period_id)
    {
        $this->setDates($end_date);

        $this->process();
    }

    public function getEndDate()
    {
        if (!isset($this->end_date)) {
            throw new Exception("End date is not set.");
        }

        return $this->end_date;
    }

    public function setEndDate($end_date)
    {
        $this->throwIfInvalidDateFormat($end_date);
        $this->end_date = $end_date;
    }

    public function getStartDate()
    {
        if (!isset($this->start_date)) {
            throw new Exception("Start date is not set.");
        }
        return $this->start_date;
    }

    public function setStartDate($start_date)
    {
        $this->throwIfInvalidDateFormat($start_date);
        $this->start_date = $start_date;
    }
}