<?php

namespace Commissions\Admin;
use Carbon\Carbon;
use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;
use PDO;
use Commissions\QueryHelper;

class Dashboard
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getNewCustomerCount($start_date, $end_date)
    {
        $sql = "
            SELECT 
                COUNT(1) customer_count
            FROM users u
            WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                AND (
                    EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :customers))
                    -- or katong mga nag upgrade to endorser
                    OR EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = u.id AND FIND_IN_SET(a.initial_cat_id, :customers1))
                )
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "  
        ";

        $customers = config('commission.member-types.customers');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":customers", $customers);
        $stmt->bindParam(":customers1", $customers);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getNewCustomers($start_date, $end_date)
    {
        $sql = "
            SELECT 
                u.id AS member_id,
                CONCAT(u.fname, ' ', u.lname) member,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(t.id, 'NO ORDERS YET') order_id,
                getCommissionValue(t.id) cv,
                IFNULL(t.amount, 0) amount_paid,
                IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
                IF(t.id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, :affiliates)), 'Endorser', 'Customer'), 
                    IF(FIND_IN_SET(t.sponsor_catid, :affiliates_1), 'Endorser', 'Customer')
                ) sponsor_type,
                t.shipcity shipping_city,
                t.shipstate shipping_state,
                IF(EXISTS(SELECT 1 FROM oc_autoship oa WHERE oa.customer_id = u.id AND DATE(oa.purchasedate) = DATE(u.created) AND oa.is_active = 1), 'Y', 'N') has_subscription,
                IFNULL(u.cellphone, u.dayphone) AS cellphone
            FROM users u
            JOIN users s ON s.id = u.sponsorid
            LEFT JOIN transactions t ON t.userid = u.id AND t.id = (
                SELECT tt.transaction_id 
                FROM v_cm_transactions tt
                WHERE tt.type = 'product' 
                    AND tt.user_id = u.id
                ORDER BY tt.transaction_date ASC LIMIT 1
            )
            WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                AND (
                    EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :customers))
                    -- or katong mga nag upgrade to endorser
                    OR EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = u.id AND FIND_IN_SET(a.initial_cat_id, :customers1))
                )
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "  
        ";

        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":customers", $customers);
        $stmt->bindParam(":customers1", $customers);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates_1", $affiliates);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewCustomerWithProductSubscriptionCount($start_date, $end_date)
    {
        $sql = "
            SELECT 
                COUNT(1) customer_count
            FROM users u
            JOIN oc_autoship oa ON oa.customer_id = u.id AND oa.is_active = 1 AND DATE(oa.purchasedate) = DATE(u.created) 
            WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                AND (
                    EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :customers))
                    -- or katong mga nag upgrade to endorser
                    OR EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = u.id AND FIND_IN_SET(a.initial_cat_id, :customers1))
                )
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "  
        ";

        $customers = config('commission.member-types.customers');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":customers", $customers);
        $stmt->bindParam(":customers1", $customers);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getNewCustomersWithProductSubscription($start_date, $end_date)
    {
        $sql = "
            SELECT 
                u.id AS member_id,
                CONCAT(u.fname, ' ', u.lname) member,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(t.id, 'NO ORDERS YET') order_id,
                getCommissionValue(t.id) cv,
                IFNULL(t.amount, 0) amount_paid,
                IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
                IF(t.id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, :affiliates)), 'Endorser', 'Customer'), 
                    IF(FIND_IN_SET(t.sponsor_catid, :affiliates_1), 'Endorser', 'Customer')
                ) sponsor_type,
                t.shipcity shipping_city,
                t.shipstate shipping_state,
                'Y' has_subscription,
                COALESCE(u.cellphone, u.dayphone, 0) AS cellphone
            FROM users u
            JOIN users s ON s.id = u.sponsorid
            JOIN oc_autoship oa ON oa.customer_id = u.id AND oa.is_active = 1 AND DATE(oa.purchasedate) = DATE(u.created) 
            LEFT JOIN transactions t ON t.userid = u.id AND t.id = (
                SELECT tt.transaction_id 
                FROM v_cm_transactions tt
                WHERE tt.type = 'product' 
                    AND tt.user_id = u.id
                ORDER BY tt.transaction_date ASC LIMIT 1
            )
            WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                AND (
                    EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :customers))
                    -- or katong mga nag upgrade to endorser
                    OR EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = u.id AND FIND_IN_SET(a.initial_cat_id, :customers_1))
                )
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "  
        ";

        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":customers", $customers);
        $stmt->bindParam(":customers_1", $customers);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates_1", $affiliates);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewEndorserCount($start_date, $end_date)
    {
        $sql = "
            SELECT 
                COUNT(1) endorser_count
            FROM cm_affiliates a
            JOIN users u ON u.id = a.user_id
            JOIN users s ON s.id = u.sponsorid
            WHERE a.affiliated_date BETWEEN :start_date AND :end_date
                AND FIND_IN_SET(a.cat_id, :affiliates)  
                AND a.initial_cat_id <> 8046 -- inactive migrated endorser plan
                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "     
        ";

        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getNewEndorsers($start_date, $end_date)
    {
        $sql = "
            SELECT 
                a.user_id AS member_id,
                CONCAT(u.fname, ' ', u.lname) member,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(t.id, 'NO ORDERS YET') order_id,
                getCommissionValue(t.id) cv,
                IFNULL(t.amount, 0) amount_paid,
                IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
                IF(t.id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, :affiliates_1)), 'Endorser', 'Customer'), 
                    IF(FIND_IN_SET(t.sponsor_catid, :affiliates_2), 'Endorser', 'Customer')
                ) sponsor_type,
                t.shipcity shipping_city,
                t.shipstate shipping_state,
                IF(EXISTS(SELECT 1 FROM oc_autoship oa WHERE oa.customer_id = u.id AND DATE(oa.purchasedate) = a.affiliated_date AND oa.is_active = 1), 'Y', 'N') has_subscription,
                IFNULL(u.cellphone, u.dayphone) AS cellphone
            FROM cm_affiliates a
            JOIN users u ON u.id = a.user_id
            JOIN users s ON s.id = u.sponsorid
            LEFT JOIN transactions t ON t.userid = u.id AND t.id = (
                SELECT tt.transaction_id 
                FROM v_cm_transactions tt 
                WHERE tt.type = 'product' 
                    AND tt.user_id = a.user_id
                    AND DATE(tt.transaction_date) = a.affiliated_date
                    AND NOT EXISTS(
                        SELECT 1 
                        FROM transaction_products tp 
                        WHERE 
                            tp.transaction_id = tt.transaction_id 
                        HAVING 
                            COUNT(1) = 1 
                            AND COUNT(IF(tp.shoppingcart_product_id = 0, 1, NULL)) = 1
                    )
                    AND tt.user_id = u.id
                ORDER BY tt.transaction_date ASC LIMIT 1
            )
            WHERE a.affiliated_date BETWEEN :start_date AND :end_date
                AND FIND_IN_SET(a.cat_id, :affiliates)  
                AND a.initial_cat_id <> 8046 -- inactive migrated endorser plan
                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "     
        ";

        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates_1", $affiliates);
        $stmt->bindParam(":affiliates_2", $affiliates);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewEndorserWithProductSubscriptionCount($start_date, $end_date)
    {
        $sql = "
            SELECT 
                COUNT(1) endorser_count
            FROM cm_affiliates a
            JOIN users u ON u.id = a.user_id
            JOIN users s ON s.id = u.sponsorid
            JOIN oc_autoship oa ON oa.customer_id = u.id AND oa.is_active = 1 AND DATE(oa.purchasedate) = a.affiliated_date
            WHERE a.affiliated_date BETWEEN :start_date AND :end_date
                AND FIND_IN_SET(a.cat_id, :affiliates)  
                AND a.initial_cat_id <> 8046 -- inactive migrated endorser plan
                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "     
        ";

        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getNewEndorsersWithProductSubscription($start_date, $end_date)
    {
        $sql = "
            SELECT 
                a.user_id AS member_id,
                CONCAT(u.fname, ' ', u.lname) member,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(t.id, 'NO ORDERS YET') order_id,
                getCommissionValue(t.id) cv,
                IFNULL(t.amount, 0) amount_paid,
                IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
                IF(t.id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, :affiliates)), 'Endorser', 'Customer'), 
                    IF(FIND_IN_SET(t.sponsor_catid, :affiliates_1), 'Endorser', 'Customer')
                ) sponsor_type,
                t.shipcity shipping_city,
                t.shipstate shipping_state,
                'Y' has_subscription,
                IFNULL(u.cellphone, u.dayphone) AS cellphone
            FROM cm_affiliates a
            JOIN users u ON u.id = a.user_id
            JOIN users s ON s.id = u.sponsorid
            JOIN oc_autoship oa ON oa.customer_id = u.id AND oa.is_active = 1 AND DATE(oa.purchasedate) = a.affiliated_date
            LEFT JOIN transactions t ON t.userid = u.id AND t.id = (
                SELECT tt.transaction_id 
                FROM v_cm_transactions tt 
                WHERE tt.type = 'product' 
                    AND tt.user_id = a.user_id
                    AND DATE(tt.transaction_date) = a.affiliated_date
                    AND NOT EXISTS(
                        SELECT 1 
                        FROM transaction_products tp 
                        WHERE 
                            tp.transaction_id = tt.transaction_id 
                        HAVING 
                            COUNT(1) = 1 
                            AND COUNT(IF(tp.shoppingcart_product_id = 0, 1, NULL)) = 1
                    )
                    AND tt.user_id = u.id
                ORDER BY tt.transaction_date ASC LIMIT 1
            )
            WHERE a.affiliated_date BETWEEN :start_date AND :end_date
                AND FIND_IN_SET(a.cat_id, :affiliates_2)  
                AND a.initial_cat_id <> 8046 -- inactive migrated endorser plan
                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "     
        ";

        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates_1", $affiliates);
        $stmt->bindParam(":affiliates_2", $affiliates);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAverageReorder($start_date, $end_date)
    {
        $sql = "
            SELECT
                ROUND(((
                    SELECT 
                        COUNT(DISTINCT t.user_id)
                    FROM v_cm_transactions t
                    WHERE t.transaction_date BETWEEN :start_date AND :end_date
                        AND t.transaction_id <> (
                                SELECT tt.transaction_id
                                FROM v_cm_transactions tt
                                WHERE tt.user_id = t.user_id
                                    AND tt.type = 'product'
                                ORDER BY tt.transaction_date ASC
                                LIMIT 1
                        )
                        AND t.type = 'product'
                        AND " . QueryHelper::NotExistsUnderBen('t.user_id') . "
                ) /
                (
                
                    SELECT 
                        COUNT(DISTINCT t.user_id)
                    FROM v_cm_transactions t
                    WHERE t.transaction_date BETWEEN :start_date1 AND :end_date1
                        AND t.type = 'product'
                        AND " . QueryHelper::NotExistsUnderBen('t.user_id') . "
                )) * 100, 2)   
        ";


        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":start_date1", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":end_date1", $end_date);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getViralIndex($start_date, $end_date)
    {
        $last_month = $this->getNewEnrolleeLastMonth($end_date);
        $this_month = $this->getNewEnrolleFromParent($end_date, array_column($last_month['users'], 'id'));

        $viral_index = round(count($this_month['users']) / count($last_month['users']), 3);

        unset($last_month['users']);
        unset($this_month['users']);

        return compact('viral_index', 'last_month', 'this_month');
    }

    private function getNewEnrolleFromParent($date, $parent_ids)
    {
        $start_date = date("Y-m-01", strtotime($date));
        $end_date = $date;

        $ids = implode(",", $parent_ids);
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                SELECT 
                    u.id AS user_id,
                    u.sponsorid AS parent_id,
                    1 AS `level`
                FROM users u
                WHERE u.levelid = 3
                    AND DATE(u.created) BETWEEN :start_date AND :end_date 
                    AND u.sponsorid IN($ids)
                    
                
                UNION ALL
                
                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    downline.`level` + 1 `level`
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                    -- over kill hahaha
                 WHERE p.levelid = 3 
                    AND DATE(p.created) BETWEEN :start_date1 AND :end_date1
                    AND p.id NOT IN ($ids)
            )
            SELECT 
                 user_id AS id
            FROM downline d 
            WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = d.user_id AND FIND_IN_SET(cm.catid, :category_ids));
        ";

        $category_ids = config('commission.member-types.customers') . ',' . config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":start_date1", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":end_date1", $end_date);
        $stmt->bindParam(":category_ids", $category_ids);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return compact('users', 'start_date', 'end_date');
    }

    private function getNewEnrolleeLastMonth($date)
    {
        $start_date = Carbon::createFromFormat("Y-m-d", $date)->modify("first day of previous month");
        $end_date = $start_date->copy()->endOfMonth();

        $start_date = $start_date->format("Y-m-d");
        $end_date = $end_date->format("Y-m-d");

        $sql = "
            SELECT
                u.id
            FROM users u
            WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                -- AND EXISTS(SELECT 1 FROM v_cm_transactions t WHERE t.type = 'product' AND t.user_id = u.id AND t.transaction_date BETWEEN @start_date AND @end_date LIMIT 1)
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :category_ids))
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "
        ";

        $category_ids = config('commission.member-types.customers') . ',' . config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":category_ids", $category_ids);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return compact('users', 'start_date', 'end_date');
    }

    private function getPackTotalSales($pack_ids, $start_date, $end_date)
    {
        $affiliates = config('commission.member-types.affiliates');

        $sql = "
            SELECT
                ROUND(SUM(tp.total), 2)
            FROM v_cm_transactions t
            JOIN transaction_products tp ON tp.transaction_id = t.transaction_id
            WHERE t.type = 'product'
                AND t.transaction_date BETWEEN :start_date AND :end_date
                AND FIND_IN_SET(tp.shoppingcart_product_id, :pack_ids)
                AND " . QueryHelper::NotExistsUnderBen('t.user_id') . "  
        ";

        $sql = "
            SELECT
                ROUND(SUM(getVolume(a.transaction_id)), 2)
            FROM (
                SELECT 
                    aa.user_id,
                    (
                       SELECT t.transaction_id 
                       FROM v_cm_transactions t 
                       WHERE t.type = 'product' 
                           AND t.user_id = aa.user_id
                           AND t.transaction_date = aa.affiliated_date
                           AND NOT EXISTS(
                               SELECT 1 
                               FROM transaction_products tp 
                               WHERE 
                                   tp.transaction_id = t.transaction_id 
                               HAVING 
                                   COUNT(1) = 1 
                                   AND COUNT(IF(tp.shoppingcart_product_id = 0, 1, NULL)) = 1
                           )
                           AND FIND_IN_SET(t.purchaser_catid, :affiliates)
                       ORDER BY t.transaction_date ASC LIMIT 1
                    ) transaction_id
                FROM cm_affiliates aa
                WHERE aa.affiliated_date BETWEEN :start_date AND :end_date
                    AND aa.initial_cat_id <> 8046
                    AND FIND_IN_SET(aa.cat_id, :affiliates1)
                    AND " . QueryHelper::NotExistsUnderBen('aa.user_id') . " 
                HAVING transaction_id IS NOT NULL
            ) a
            WHERE EXISTS(
                SELECT 1 FROM transaction_products tp 
                WHERE tp.transaction_id = a.transaction_id 
                AND FIND_IN_SET(tp.shoppingcart_product_id, :pack_ids)
			)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates1", $affiliates);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":pack_ids", $pack_ids);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    private function getPackSales($pack_ids, $start_date, $end_date)
    {
        $affiliates = config('commission.member-types.affiliates');
        $sql = "
            SELECT
                t.user_id purchaser_id,
                CONCAT(u.fname, ' ', u.lname) purchaser,
                tt.invoice,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor
            FROM v_cm_transactions t
            JOIN transactions tt ON tt.id = t.transaction_id
            JOIN users u ON u.id = t.user_id
            JOIN users s ON s.id = t.sponsorid
            WHERE t.type = 'product'
                AND t.transaction_date BETWEEN :start_date AND :end_date
                AND EXISTS(
					 	SELECT 1 FROM transaction_products tp 
						WHERE tp.transaction_id = t.transaction_id 
							AND FIND_IN_SET(tp.shoppingcart_product_id, :pack_ids)
				)
                AND " . QueryHelper::NotExistsUnderBen('t.user_id') . "  
        ";

        $sql = "
            SELECT
                a.user_id purchaser_id,
                CONCAT(u.fname, ' ', u.lname) purchaser,
                tt.invoice,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(tt.id, 'NO ORDERS YET') order_id,
                getCommissionValue(tt.id) cv,
                IFNULL(tt.amount, 0) amount_paid,
                IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = tt.id), 'Y', 'N') has_coupon,
                IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = tt.id), 'Y', 'N') has_gift_card,
                IF(tt.id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, :affiliates_2)), 'Endorser', 'Customer'), 
                    IF(FIND_IN_SET(tt.sponsor_catid, :affiliates_1), 'Endorser', 'Customer')
                ) sponsor_type,
                tt.shipcity shipping_city,
                tt.shipstate shipping_state
            FROM (
                SELECT 
                    aa.user_id,
                    (
                       SELECT t.transaction_id 
                       FROM v_cm_transactions t 
                       WHERE t.type = 'product' 
                           AND t.user_id = aa.user_id
                           AND t.transaction_date = aa.affiliated_date
                           AND NOT EXISTS(
                               SELECT 1 
                               FROM transaction_products tp 
                               WHERE 
                                   tp.transaction_id = t.transaction_id 
                               HAVING 
                                   COUNT(1) = 1 
                                   AND COUNT(IF(tp.shoppingcart_product_id = 0, 1, NULL)) = 1
                           )
                           AND FIND_IN_SET(t.purchaser_catid, :affiliates)
                       ORDER BY t.transaction_date ASC LIMIT 1
                    ) transaction_id
                FROM cm_affiliates aa
                WHERE aa.affiliated_date BETWEEN :start_date AND :end_date
                    AND aa.initial_cat_id <> 8046
                    AND FIND_IN_SET(aa.cat_id, :affiliates1)
                    AND " . QueryHelper::NotExistsUnderBen('aa.user_id') . " 
                HAVING transaction_id IS NOT NULL
            ) a
            JOIN users u ON u.id = a.user_id
            JOIN transactions tt ON tt.id = a.transaction_id
            JOIN users s ON s.id = tt.sponsorid
            WHERE EXISTS(
                SELECT 1 FROM transaction_products tp 
                WHERE tp.transaction_id = a.transaction_id 
                AND FIND_IN_SET(tp.shoppingcart_product_id, :pack_ids)
			)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates1", $affiliates);
        $stmt->bindParam(":affiliates_1", $affiliates);
        $stmt->bindParam(":affiliates_2", $affiliates);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":pack_ids", $pack_ids);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCustomerPackTotalSales($pack_ids, $start_date, $end_date)
    {
        $sql = "
            SELECT 
                IFNULL(SUM(tp.total), 0)
            FROM users u
            JOIN users s ON s.id = u.sponsorid
            JOIN transactions t ON t.userid = u.id AND t.id = (
                SELECT tt.transaction_id 
                FROM v_cm_transactions tt
                WHERE tt.type = 'product' 
                    AND tt.user_id = u.id
                ORDER BY tt.transaction_date ASC LIMIT 1
            )
            JOIN transaction_products tp ON tp.transaction_id = t.id
            WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                AND (
                    EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :customers))
                    -- or katong mga nag upgrade to endorser
                    OR EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = u.id AND FIND_IN_SET(a.initial_cat_id, :customers_1))
                )
                AND FIND_IN_SET(tp.shoppingcart_product_id, :pack_ids)
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "
        ";

        $customers = config('commission.member-types.customers');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":customers", $customers);
        $stmt->bindParam(":customers_1", $customers);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":pack_ids", $pack_ids);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    private function getCustomerPackSales($pack_ids, $start_date, $end_date)
    {
        $sql = "
            SELECT                
                u.id AS purchaser_id,
                CONCAT(u.fname, ' ', u.lname) purchaser,
                t.invoice,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(t.id, 'NO ORDERS YET') order_id,
                getCommissionValue(t.id) cv,
                IFNULL(t.amount, 0) amount_paid,
                IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
                IF(t.id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, :affiliates)), 'Endorser', 'Customer'), 
                    IF(FIND_IN_SET(t.sponsor_catid, :affiliates_1), 'Endorser', 'Customer')
                ) sponsor_type,
                t.shipcity shipping_city,
                t.shipstate shipping_state
            FROM users u
            JOIN users s ON s.id = u.sponsorid
            JOIN transactions t ON t.userid = u.id AND t.id = (
                SELECT tt.transaction_id 
                FROM v_cm_transactions tt
                WHERE tt.type = 'product' 
                    AND tt.user_id = u.id
                ORDER BY tt.transaction_date ASC LIMIT 1
            )
            JOIN transaction_products tp ON tp.transaction_id = t.id
            WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                AND (
                    EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :customers))
                    -- or katong mga nag upgrade to endorser
                    OR EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = u.id AND FIND_IN_SET(a.initial_cat_id, :customers_1))
                )
                AND FIND_IN_SET(tp.shoppingcart_product_id, :pack_ids)
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "
            GROUP BY t.id
        ";

        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates_1", $affiliates);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":customers", $customers);
        $stmt->bindParam(":customers_1", $customers);
        $stmt->bindParam(":pack_ids", $pack_ids);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCustomerTransformationPackTotalSales($start_date, $end_date)
    {
        return $this->getCustomerPackTotalSales(config('commission.ach13ve-packs.customer-transformation'), $start_date, $end_date);
    }

    public function getCustomerTransformationPackSales($start_date, $end_date)
    {
        return $this->getCustomerPackSales(config('commission.ach13ve-packs.customer-transformation'), $start_date, $end_date);
    }

    public function getTransformationPackTotalSales($start_date, $end_date)
    {
        return $this->getPackTotalSales(config('commission.ach13ve-packs.transformation'), $start_date, $end_date);
    }

    public function getElitePackTotalSales($start_date, $end_date)
    {
        return $this->getPackTotalSales(config('commission.ach13ve-packs.elite'), $start_date, $end_date);
    }

    public function getFamilyElitePackTotalSales($start_date, $end_date)
    {
        return $this->getPackTotalSales(config('commission.ach13ve-packs.elite-family'), $start_date, $end_date);
    }

    public function getTransformationPackSales($start_date, $end_date)
    {
        return $this->getPackSales(config('commission.ach13ve-packs.transformation'), $start_date, $end_date);
    }

    public function getElitePackSales($start_date, $end_date)
    {
        return $this->getPackSales(config('commission.ach13ve-packs.elite'), $start_date, $end_date);
    }

    public function getFamilyElitePackSales($start_date, $end_date)
    {
        return $this->getPackSales(config('commission.ach13ve-packs.elite-family'), $start_date, $end_date);
    }

    public function getTopEndorsers($start_date, $end_date)
    {
        $sql = "
            SELECT
                a.user_id,
                CONCAT(u.fname, ' ', u.lname) endorser,
                IFNULL(c.endorser_count, 0) AS endorser_count,
                IFNULL(v.volume, 0) AS volume
            FROM cm_affiliates a
            JOIN users u ON u.id = a.user_id
            LEFT JOIN (
                SELECT 
                    COUNT(1) endorser_count,
                    u.sponsorid AS user_id
                FROM cm_affiliates aa
                JOIN users u ON u.id = aa.user_id
                JOIN users s ON s.id = u.sponsorid
                WHERE aa.affiliated_date BETWEEN :start_date AND :end_date
                    AND aa.initial_cat_id <> 8046 -- inactive migrated plan
                    AND FIND_IN_SET(aa.cat_id, :affiliates)
                    AND " . QueryHelper::NotExistsUnderBen('aa.user_id') . "
                GROUP BY u.sponsorid
            ) c ON c.user_id = a.user_id
            LEFT JOIN (
                SELECT 
                     SUM((
                        SELECT getVolume(t.transaction_id)
                        FROM v_cm_transactions t 
                        WHERE t.type = 'product' 
                            AND t.user_id = aa.user_id
                            AND t.transaction_date = aa.affiliated_date
                            AND NOT EXISTS(
                                SELECT 1 
                                FROM transaction_products tp 
                                WHERE 
                                    tp.transaction_id = t.transaction_id 
                                HAVING 
                                    COUNT(1) = 1 
                                    AND COUNT(IF(tp.shoppingcart_product_id = 0, 1, NULL)) = 1
                            )
                        ORDER BY t.transaction_date ASC LIMIT 1
                    )) volume,
                    u.sponsorid AS user_id
                FROM cm_affiliates aa
                JOIN users u ON u.id = aa.user_id
                JOIN users s ON s.id = u.sponsorid
                WHERE aa.affiliated_date BETWEEN :start_date1 AND :end_date1
                    AND aa.initial_cat_id <> 8046 -- inactive migrated plan
                    AND FIND_IN_SET(aa.cat_id, :affiliates1)
                    AND " . QueryHelper::NotExistsUnderBen('aa.user_id') . "
                GROUP BY u.sponsorid
            ) v ON v.user_id = a.user_id
            WHERE EXISTS( SELECT 1 FROM categorymap c WHERE c.userid = a.user_id AND FIND_IN_SET(c.catid, :affiliates2))
                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "
            GROUP BY a.user_id
            HAVING endorser_count > 0
            ORDER BY endorser_count DESC, volume DESC
        ";

        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":start_date1", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":end_date1", $end_date);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates1", $affiliates);
        $stmt->bindParam(":affiliates2", $affiliates);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEndorsersIncludingFirstPurchase($user_id, $start_date, $end_date)
    {
        $sql = "
            SELECT
                a.user_id AS member_id,
                CONCAT(u.fname, ' ', u.lname) member,
                a.affiliated_at,
                t.transactiondate AS transaction_date,
                t.invoice,
                (
                    SELECT 
                        GROUP_CONCAT(CONCAT(tp.quantity, 'x ', op.model) SEPARATOR '<br />')
                    FROM transaction_products tp
                    JOIN oc_product op ON op.product_id = tp.shoppingcart_product_id
                    WHERE tp.transaction_id = t.id
                ) description,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(t.id, 'NO ORDERS YET') order_id,
                getCommissionValue(t.id) cv,
                IFNULL(t.amount, 0) amount_paid,
                IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
                IF(t.id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, :affiliates_2)), 'Endorser', 'Customer'), 
                    IF(FIND_IN_SET(t.sponsor_catid, :affiliates_1), 'Endorser', 'Customer')
                ) sponsor_type,
                t.shipcity shipping_city,
                t.shipstate shipping_state,
                IFNULL(u.cellphone, u.dayphone) AS cellphone
            FROM (
                SELECT 
                    aa.user_id,
                    aa.affiliated_at,
                    (
                        SELECT t.transaction_id 
                        FROM v_cm_transactions t 
                        WHERE t.type = 'product' 
                            AND t.user_id = aa.user_id
                            AND t.transaction_date = aa.affiliated_date
                            AND NOT EXISTS(
                                SELECT 1 
                                FROM transaction_products tp 
                                WHERE 
                                    tp.transaction_id = t.transaction_id 
                                HAVING 
                                    COUNT(1) = 1 
                                    AND COUNT(IF(tp.shoppingcart_product_id = 0, 1, NULL)) = 1
                            )
                        ORDER BY t.transaction_date ASC LIMIT 1
                    ) transaction_id -- first successful purchase as endorser
                FROM cm_affiliates aa
                JOIN users u ON u.id = aa.user_id
                JOIN users s ON s.id = u.sponsorid
                WHERE aa.affiliated_date BETWEEN :start_date AND :end_date
                  AND aa.initial_cat_id <> 8046 -- inactive migrated plan
                  AND FIND_IN_SET(aa.cat_id, :affiliates)
                  AND " . QueryHelper::NotExistsUnderBen('aa.user_id') . "
                  AND u.sponsorid = :sponsor_id
            ) a
            JOIN users u ON u.id = a.user_id
            JOIN users s ON s.id = u.sponsorid
            LEFT JOIN transactions t ON t.id = a.transaction_id
            -- HAVING a.affiliated_date <> t.transaction_date
        ";

        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":sponsor_id", $user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":affiliates", $affiliates);
        $stmt->bindParam(":affiliates_1", $affiliates);
        $stmt->bindParam(":affiliates_2", $affiliates);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function _getViralIndex($start_date, $end_date)
    {
        // https://blog.appvirality.com/what-is-viral-coefficient-and-why-you-need-it-to-make-your-app-go-viral/
        $sql = "
            SELECT 
                ROUND(
                    (SELECT
                        COUNT(1)
                    FROM users u
                    WHERE DATE(u.created) BETWEEN :start_date AND :end_date
                        AND EXISTS(SELECT 1 FROM v_cm_transactions t WHERE t.type = 'product' AND t.user_id = u.id AND t.transaction_date BETWEEN :start_date1 AND :end_date1 LIMIT 1)
                        AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '14,15,16')))
                    /
                    (SELECT
                        COUNT(1)
                    FROM users u
                    WHERE 
                        DATE(u.created) < :start_date2
                        AND EXISTS(SELECT 1 FROM v_cm_transactions t WHERE t.type = 'product' AND t.user_id = u.id AND t.transaction_date < :start_date3 LIMIT 1)
                        AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '14,15,16')))
                , 2)
        ";


        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":start_date1", $start_date);
        $stmt->bindParam(":start_date2", $start_date);
        $stmt->bindParam(":start_date3", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":end_date1", $end_date);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    const REPORT_NEW_CUSTOMERS = "NEW_CUSTOMERS";
    const REPORT_NEW_CUSTOMERS_PS = "NEW_CUSTOMERS_PS";
    const REPORT_NEW_ENDORSERS = "NEW_ENDORSERS";
    const REPORT_NEW_ENDORSERS_PS = "NEW_ENDORSERS_PS";
    const REPORT_TRANSFORMATION_PACK = "TRANSFORMATION_PACK";
    const REPORT_ELITE_PACK = "ELITE_PACK";
    const REPORT_FAMILY_PACK = "FAMILY_PACK";
    const REPORT_TOP_ENDORSERS = "TOP_ENDORSERS";
    const REPORT_TOP_ENDORSERS_ENDORSER = "TOP_ENDORSERS_ENDORSER";
    const REPORT_CUSTOMER_TRANSFORMATION_PACK = "CUSTOMER_TRANSFORMATION_PACK";

    public function getDownloadLink($report_type, $start_date, $end_date, $user_id = null)
    {
        $csv = new CsvReport("csv/admin/dashboard");

        $data = [
            ['no data' => 'no data']
        ];

        $filename = $report_type;

        switch ($report_type) {
            case static::REPORT_NEW_CUSTOMERS:
                $data = $this->getNewCustomers($start_date, $end_date);
                break;
            case static::REPORT_NEW_CUSTOMERS_PS:
                $data = $this->getNewCustomersWithProductSubscription($start_date, $end_date);
                break;
            case static::REPORT_NEW_ENDORSERS:
                $data = $this->getNewEndorsers($start_date, $end_date);
                break;
            case static::REPORT_NEW_ENDORSERS_PS:
                $data = $this->getNewEndorsersWithProductSubscription($start_date, $end_date);
                break;
            case static::REPORT_TRANSFORMATION_PACK:
                $data = $this->getTransformationPackSales($start_date, $end_date);
                break;
            case static::REPORT_ELITE_PACK:
                $data = $this->getElitePackSales($start_date, $end_date);
                break;
            case static::REPORT_FAMILY_PACK:
                $data = $this->getFamilyElitePackSales($start_date, $end_date);
                break;
            case static::REPORT_TOP_ENDORSERS:
                $data = $this->getTopEndorsers($start_date, $end_date);
                break;
            case static::REPORT_TOP_ENDORSERS_ENDORSER:
                $data = $this->getEndorsersIncludingFirstPurchase($user_id, $start_date, $end_date);
                $filename .= "-$user_id";
                break;
            case static::REPORT_CUSTOMER_TRANSFORMATION_PACK:
                $data = $this->getCustomerTransformationPackSales($start_date, $end_date);
                break;
        }

        return $csv->generateLink("$filename-$start_date-$end_date", $data);
    }

}