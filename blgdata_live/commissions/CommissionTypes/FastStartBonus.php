<?php

namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;

class FastStartBonus extends CommissionType implements CommissionTypeInterface
{
    public function count()
    {
        return count($this->getOrders());
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

    public function getBonus($product_id)
    {
        switch ($product_id) {
            case 13:
                $amount = 30;
                break;
            case 14:
                $amount = 60;
                break;
            case 15:
                $amount = 200;
            break;
            default:
                $amount = 0;
            break;
        }

        return $amount;
    }

    private function getOrders($start = null, $length = null)
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
}