<?php


namespace Commissions\CommissionTypes;


use App\CommissionPeriod;
use Commissions\BackgroundWorkerLogger;
use Commissions\Contracts\CommissionTypeInterface;
use Commissions\Repositories\PayoutRepository;
use Illuminate\Support\Facades\DB as DB;
use Commissions\QueryHelper;

class CustomerProfit extends CommissionType implements CommissionTypeInterface
{

    public function count()
    {
        return count($this->getOrders());
    }

    public function generateCommission($start, $length)
    {
        $orders = $this->getOrders($start, $length);

        foreach ($orders as $key => $order) {
            $this->log("Processing Order ID " . $order['order_id']);
            $user_id = $order['user_id'];
            $sponsor_id = $order['sponsor_id'];
            $commission_value = $order['price'];
            $payee_id = $order['payee_id'];
            $order_id = $order['order_id'];
            $percentage = +$order['subscription_purchase'] === 1 ? 0.10 : 0.20; //if autoship/subscription purchase 10%
            $is_autoship_order = +$order['subscription_purchase'] === 1 ? "Yes" : "No";
            $order_type = $order['order_type'];
            $product_name = $order['product_name'];

            if($commission_value > 0){
                $this->insertPayout(
                    $payee_id,
                    $user_id,
                    $commission_value,
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

    private function getOrders($start = null, $length = null)
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
                    'Customer Order' AS order_type
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
	            JOIN users s ON s.id = t.sponsor_id
                JOIN transaction_products tp ON tp.transaction_id = t.transaction_id
	            JOIN oc_product op ON op.`product_id` = tp.`shoppingcart_product_id`
                WHERE t.type = 'product' AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
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
                    'Personal Order' AS order_type
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
	            JOIN users s ON s.id = t.sponsor_id
                JOIN transaction_products tp ON tp.transaction_id = t.transaction_id
	            JOIN oc_product op ON op.`product_id` = tp.`shoppingcart_product_id`
                WHERE t.type = 'product' 
                AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
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

}