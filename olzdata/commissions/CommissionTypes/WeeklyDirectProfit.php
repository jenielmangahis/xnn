<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class WeeklyDirectProfit extends CommissionType implements CommissionTypeInterface
{

    const BONUS_PERCENTAGE = 0.25;
    const CUSTOMER_ORDER = 'Customer Order';
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
            $payee_id = $order['payee_id'];
            $commission_value = $order['cv'];
            $order_id = $order['order_id'];
            $level = $order['level'];
            $percentage = self::BONUS_PERCENTAGE;
            $order_type = $order['order_type'];

            if($order_type == self::CUSTOMER_ORDER) {

                $customer = $this->isCustomer($sponsor_id);

                if(count($customer) > 0) {
                    $upline_payee_id = $this->findFirstUplineRepresentative($user_id);
                    if($upline_payee_id) {

                        $this->insertPayout(
                            $upline_payee_id,
                            $user_id,
                            $commission_value,
                            $percentage * 100,
                            $commission_value * $percentage,
                            "Order type: $order_type | Member: $upline_payee_id has a total of $commission_value CV is paid to the first upline Representative: $user_id",
                            $order_id,
                            $level,
                            $sponsor_id
                        );
                    }
                }
            } else {
                $this->insertPayout(
                    $payee_id,
                    $user_id,
                    $commission_value,
                    $percentage * 100,
                    $commission_value * $percentage,
                    "Order type: $order_type | Member: $payee_id has a total of $commission_value CV",
                    $order_id,
                    $level,
                    $sponsor_id
                );
            }

            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    private function isCustomer($user_id)
    {
        $customers = config('commission.member-types.customers');
        $sql = "
            SELECT * FROM categorymap cm WHERE cm.userid = $user_id AND FIND_IN_SET(cm.catid, '$customers')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

    private function getOrders($start = null, $length = null)
    {
        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $sql = "SELECT
                    a.order_id, 
                    a.user_id,
                    a.sponsor_id,
                    a.payee_id,
                    a.cv,
                    a.transaction_date,
                    a.`level`,
                    a.order_type
                FROM (
                WITH RECURSIVE downline (user_id, parent_id, `level`, root_id) AS (
                    SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    1 AS `level`,
                    sponsorid AS root_id
                    FROM users u
                    WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers'))
                    
                    UNION ALL
                    
                    SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    downline.`level` + 1 `level`,
                    downline.root_id
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.id AND FIND_IN_SET(cm.catid, '$customers'))
                )
                SELECT
                    t.transaction_id AS order_id, 
                    t.user_id,
                    t.sponsor_id,
                    d.root_id AS payee_id,
                    COALESCE(t.computed_cv, 0) AS cv,
                    t.transaction_date,
                    d.level,
                    'Customer Order' AS order_type
                FROM downline d
                JOIN v_cm_transactions t ON t.user_id = d.user_id
                WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'
                    AND t.`type` = 'product' 
                    AND FIND_IN_SET(t.purchaser_catid, '$customers')
                    
                UNION ALL
                
                SELECT
                    t.transaction_id AS order_id, 
                    t.user_id,
                    t.sponsor_id,
                    t.user_id AS payee_id,
                    COALESCE(t.computed_cv, 0) AS cv,
                    t.transaction_date,
                    0 `level`,
                    'Representative Order' AS order_type
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
                WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'
                    AND t.`type` = 'product'
                    AND FIND_IN_SET(t.purchaser_catid, '$affiliates')
                    AND u.active = 'Yes'
                ) a
                ORDER BY a.user_id";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function isQualifiedForWeeklyDirectProfit($user_id)
    {
        $db = DB::connection()->getPdo();
        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');

        $start_date = date('Y-m-d',strtotime('last thursday'));
        $end_date = date('Y-m-d',strtotime('this wednesday'));

        $sql = "
            SELECT
                a.order_id, 
                a.user_id,
                a.sponsor_id,
                a.payee_id,
                a.cv,
                a.transaction_date,
                a.`level`,
                a.order_type
            FROM (
            WITH RECURSIVE downline (user_id, parent_id, `level`, root_id) AS (
                SELECT 
                id AS user_id,
                sponsorid AS parent_id,
                1 AS `level`,
                sponsorid AS root_id
                FROM users u
                WHERE u.id = $user_id
                
                UNION ALL
                
                SELECT
                p.id AS user_id,
                p.sponsorid AS parent_id,
                downline.`level` + 1 `level`,
                downline.root_id
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.id AND FIND_IN_SET(cm.catid, '$customers'))
            )
            SELECT
                t.transaction_id AS order_id, 
                t.user_id,
                t.sponsor_id,
                d.root_id AS payee_id,
                COALESCE(t.computed_cv, 0) AS cv,
                t.transaction_date,
                d.level,
                'Customer Order' AS order_type
            FROM downline d
            JOIN v_cm_transactions t ON t.user_id = d.user_id
            WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND t.`type` = 'product' 
                AND FIND_IN_SET(t.purchaser_catid, '$customers')
            
            UNION ALL
                            
            SELECT
                t.transaction_id AS order_id, 
                t.user_id,
                t.sponsor_id,
                t.user_id AS payee_id,
                COALESCE(t.computed_cv, 0) AS cv,
                t.transaction_date,
                0 `level`,
                'Representative Order' AS order_type
            FROM v_cm_transactions t
            JOIN users u ON u.id = t.user_id
            WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND t.`type` = 'product'
                AND FIND_IN_SET(t.purchaser_catid, '$affiliates')
                AND u.active = 'Yes'
                AND u.id = $user_id
            ) a
            ORDER BY a.user_id
            
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $flag = false;
        if(count($result) > 0) {
            $flag = true;
        }

        return $flag;
    }
}