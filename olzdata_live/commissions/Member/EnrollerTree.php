<?php


namespace Commissions\Member;

use Illuminate\Support\Facades\DB;
use PDO;

class EnrollerTree
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getParentDetails($user_id, $start_date)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $default_affiliate = config('commission.affiliate');

        $sql = "
            SELECT
            EXISTS(
                SELECT 1 FROM users c
                WHERE c.sponsorid = u.id
                AND c.levelid = 3
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = c.id AND FIND_IN_SET(cm.catid, '$affiliates,$customers'))
            ) AS branch,
            u.id AS user_id,
            CONCAT(u.fname, ' ', u.lname) AS `member`,
            IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', IFNULL(r.name, 'Representative'))) AS paid_as_rank,
            IFNULL(dv.prs,0) AS prs,
            IFNULL(dv.`grs`,0) AS grs,
            IFNULL(dv.`sponsored_qualified_representatives_count`,0) AS sponsored_qualified_representatives,
            IFNULL(dv.`sponsored_leader_or_higher_users`,0) AS sponsored_leader_or_higher,
            IFNULL(dv.sponsored_leader_or_higher_count,0) AS sponsored_leader_or_higher_count,
            EXISTS(
                SELECT 1
                FROM v_cm_transactions t 
                WHERE t.user_id = u.id 
                AND t.type = 'product'
                AND t.transaction_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()
            ) has_order_last_30_days,
            IF(ca.`affiliated_date` BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY) AND CURRENT_DATE(),
              'Yes', 'No'
            ) AS has_first_90_days,
            DATE_FORMAT(ca.affiliated_date, '%Y-%m-%d') enrolled_date,
            CONCAT(s.fname,' ',s.lname) AS `sponsor`
            FROM users AS u
            LEFT JOIN users AS s ON s.id = u.sponsorid
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id
            LEFT JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            LEFT JOIN cm_ranks AS r ON r.id = dr.paid_as_rank_id
            LEFT JOIN cm_affiliates ca ON ca.user_id = u.id
            WHERE 
            dv.volume_date = '$start_date'
            AND u.id = :id
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':id', $user_id);
        $smt->execute();
        return $smt->fetch(PDO::FETCH_ASSOC);
    }

    public function getChildrenPaginate($parent_id, $start_date, $page_no = 0)
    {
        if ($page_no == 0) {
            $page_no = 1;
        }

        $no_of_records_per_page = 20;
        $offset = ($page_no - 1) * $no_of_records_per_page;

        $total_count = $this->getChildrenCount($parent_id);

        $total_pages = ceil($total_count / $no_of_records_per_page);

        $results = $this->getChildren($parent_id, $start_date, $offset, $no_of_records_per_page);
        //$results = $this->getChildren($parent_id, $offset, $no_of_records_per_page);

        $data['total_pages'] = ($page_no == $total_pages ? 1 : $total_pages);
        $data['downlines'] = $results;
        $data['pageno'] = $page_no + 1;
        $data['total_downlines'] = $total_count - ($page_no * $no_of_records_per_page);

        return $data;
    }

    private function getChildrenCount($parent_id)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $sql = "
            SELECT
                COUNT(1)
            FROM users AS u
            LEFT JOIN users AS s ON s.id = u.sponsorid
            WHERE u.levelid = 3 AND u.sponsorid = :parent_id
                -- AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates'))
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':parent_id', $parent_id);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function getChildren($parent_id, $start_date, $offset, $take)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $default_affiliate = config('commission.affiliate');

        $sql = "
            SELECT
            EXISTS(
                SELECT 1 
                FROM users c 
                WHERE c.sponsorid = u.id
                AND c.levelid = 3
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = c.id AND FIND_IN_SET(cm.catid, '$affiliates'))
            ) AS branch,
            u.id AS user_id,
            CONCAT(u.fname, ' ', u.lname) AS `member`,
            IFNULL(d.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer'
            , IFNULL(d.name, 'Representative'))) AS paid_as_rank,
            IFNULL(d.prs,0) AS prs,
            IFNULL(d.`grs`,0) AS grs,
            IFNULL(d.`sponsored_qualified_representatives_count`,0) AS sponsored_qualified_representatives,
            IFNULL(d.`sponsored_leader_or_higher_users`,0) AS sponsored_leader_or_higher,
            IFNULL(d.sponsored_leader_or_higher_count,0) AS sponsored_leader_or_higher_count,
            EXISTS(
                SELECT 1
                FROM v_cm_transactions t 
                WHERE t.user_id = u.id 
                AND t.type = 'product'
                AND t.transaction_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()
            ) has_order_last_30_days,
            IF(ca.`affiliated_date` BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY) AND CURRENT_DATE(),
            'Yes', 'No'
            ) AS first_90_days,
            DATE_FORMAT(ca.affiliated_date, '%Y-%m-%d') enrolled_date,
            CONCAT(s.fname,' ',s.lname) AS `sponsor`
            FROM users AS u
            LEFT JOIN cm_affiliates ca ON ca.user_id = u.id
            LEFT JOIN 
            (SELECT dr.user_id, r.name, dv.prs, dv.grs, dv.sponsored_qualified_representatives_count, dv.sponsored_leader_or_higher_users,
                dv.sponsored_leader_or_higher_count 
                FROM cm_daily_ranks dr 
            LEFT JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            LEFT JOIN cm_ranks AS r ON r.id = dr.paid_as_rank_id
                WHERE dv.volume_date = '$start_date'
            ) AS d ON d.user_id = u.id 
            LEFT JOIN users as s on u.sponsorid = s.id         
            WHERE u.sponsorid = :parent_id AND u.levelid = 3 
            ORDER BY 
            CASE
            WHEN paid_as_rank = 'Customer' THEN 2
            ELSE 1
            END ASC,
            u.id
            LIMIT :offset, :take
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':parent_id', $parent_id);
        $smt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $smt->bindParam(':take', $take, PDO::PARAM_INT);
        $smt->execute();
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderHistory($filters, $member_id)
    {
        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('v_cm_transactions AS t')
            ->selectRaw("
               t.transaction_id,
               t.invoice,
               (
                    SELECT
                        CONCAT('[', 
                            GROUP_CONCAT(JSON_OBJECT('quantity', tp.quantity, 'product', p.model)), 
                        ']') products
                    FROM transaction_products tp
                    JOIN oc_product p ON p.product_id = tp.shoppingcart_product_id
                    WHERE tp.transaction_id = t.transaction_id
                ) products,
                t.transaction_date,
                t.amount,
                t.computed_cv AS cv
            ")
            ->where('t.user_id', $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('t.transaction_id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('t.transaction_date', 'LIKE', "%{$search}%")
                    ->orWhereRaw("EXISTS(
                        SELECT 1 
                        FROM transaction_products tp
                        JOIN oc_product p ON p.product_id = tp.shoppingcart_product_id
                        WHERE tp.transaction_id = t.transaction_id AND p.model LIKE ?)", ["%{$search}%"]
                    );
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("t.transaction_id", "desc");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id');
    }

    public function getUserDownlines($filter, $query, $memberid) {
        
        switch ($filter) {
            case "id":
                $query = '%'.$query.'%';
                $sql = "WITH RECURSIVE cte AS 
                      (SELECT 
                        id AS user_id,
                        fname,
                        lname,
                        site,
                        email,
                        1 AS `level` 
                      FROM
                        users
                      WHERE sponsorid = :memberid
                      AND levelid = 3 
                      UNION
                      ALL 
                      SELECT 
                        p.id AS user_id,
                        p.fname,
                        p.lname,
                        p.site,
                        p.email,
                        cte.`level` + 1 `level` 
                      FROM
                        users p
                        INNER JOIN cte 
                          ON p.sponsorid = cte.user_id 
                          WHERE p.levelid = 3 
                          ) 
                      SELECT 
                        u.id, u.fname, u.lname, u.site, u.email
                      FROM
                        cte c JOIN users u ON u.id = c.user_id
                        WHERE u.levelid = 3 AND u.password IS NOT NULL
                        AND EXISTS (SELECT 1 FROM categorymap WHERE u.id = userid AND FIND_IN_SET(catid, :affiliate))
                        AND u.active != 'Canceled'
                        AND u.id LIKE :query ORDER BY u.id";
                break;
            case "fname":
                $query = '%'.$query.'%';
                $sql = "WITH RECURSIVE cte AS 
                      (SELECT 
                        id AS user_id,
                        fname,
                        lname,
                        site,
                        email,
                        1 AS `level` 
                      FROM
                        users
                      WHERE sponsorid = :memberid
                      AND levelid = 3 
                      UNION
                      ALL 
                      SELECT 
                        p.id AS user_id,
                        p.fname,
                        p.lname,
                        p.site,
                        p.email,
                        cte.`level` + 1 `level` 
                      FROM
                        users p
                        INNER JOIN cte 
                          ON p.sponsorid = cte.user_id
                          WHERE p.levelid = 3 
                          ) 
                      SELECT 
                        u.id, u.fname, u.lname, u.site, u.email
                      FROM
                        cte c JOIN users u ON u.id = c.user_id
                        WHERE u.levelid = 3 AND u.password IS NOT NULL
                        AND EXISTS (SELECT 1 FROM categorymap WHERE u.id = userid AND FIND_IN_SET(catid, :affiliate))
                        AND u.active != 'Canceled'
                        AND u.fname LIKE :query ORDER BY u.id";
                break;
            case "lname":
                $query = '%'.$query.'%';
                $sql = "WITH RECURSIVE cte AS 
                      (SELECT 
                        id AS user_id,
                        fname,
                        lname,
                        site,
                        email,
                        1 AS `level` 
                      FROM
                        users
                      WHERE sponsorid = :memberid
                      AND levelid = 3 
                      UNION
                      ALL 
                      SELECT 
                        p.id AS user_id,
                        p.fname,
                        p.lname,
                        p.site,
                        p.email,
                        cte.`level` + 1 `level` 
                      FROM
                        users p
                        INNER JOIN cte 
                          ON p.sponsorid = cte.user_id 
                          WHERE p.levelid = 3 ) 
                      SELECT 
                        u.id, u.fname, u.lname, u.site, u.email
                      FROM
                        cte c JOIN users u ON u.id = c.user_id
                        WHERE u.levelid = 3 AND u.password IS NOT NULL
                        AND EXISTS (SELECT 1 FROM categorymap WHERE u.id = userid AND FIND_IN_SET(catid, :affiliate))
                        AND u.active != 'Canceled'
                        AND u.lname LIKE :query ORDER BY u.id";
                break;
            case "site":
                $query = '%'.$query.'%';
                $sql = "
                  WITH RECURSIVE cte AS 
                  (
                    SELECT 
                    id AS user_id,
                    1 AS `level` 
                    FROM users
                    WHERE sponsorid = :memberid
                      AND levelid = 3 
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
                          WHERE p.levelid = 3 
                  ) 
                  SELECT
                    u.id, u.fname, u.lname, u.site, u.email
                  FROM cte c 
                  JOIN users u ON u.id = c.user_id
                  WHERE u.levelid = 3 AND u.password IS NOT NULL
                  AND EXISTS (SELECT 1 FROM categorymap WHERE u.id = userid AND FIND_IN_SET(catid, :affiliate))
                  AND u.active != 'Canceled'
                  AND u.site LIKE :query ORDER BY u.id
                ";
                break;
            case "level":
                $query = '%'.$query.'%';
                $sql = "
                  WITH RECURSIVE cte AS 
                  (
                    SELECT 
                    id AS user_id,
                    1 AS `level` 
                    FROM users
                    WHERE sponsorid = :memberid
                      AND levelid = 3 
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
                          WHERE p.levelid = 3 
                  ) 
                  SELECT
                    u.id, u.fname, u.lname, u.site, u.email
                  FROM cte c 
                  JOIN users u ON u.id = c.user_id
                  WHERE u.levelid = 3 AND u.password IS NOT NULL
                  AND EXISTS (SELECT 1 FROM categorymap WHERE u.id = userid AND FIND_IN_SET(catid, :affiliate))
                  AND u.active != 'Canceled'
                  AND c.level LIKE :query ORDER BY u.id
                ";
                break;
            case "title":
                $query = '%'.$query.'%';
                $sql = "
                  WITH RECURSIVE cte AS 
                  (
                    SELECT 
                    id AS user_id,
                    1 AS `level` 
                    FROM users
                    WHERE sponsorid = :memberid
                      AND levelid = 3 
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
                          WHERE p.levelid = 3 
                  ) 
                  SELECT
                    u.id, u.fname, u.lname, u.site, u.email
                  FROM cte c 
                  JOIN users u ON u.id = c.user_id
                  LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = CURRENT_DATE()
                  JOIN cm_ranks r ON r.id = dr.rank_id
                  WHERE u.levelid = 3 AND u.password IS NOT NULL
                  AND EXISTS (SELECT 1 FROM categorymap WHERE u.id = userid AND FIND_IN_SET(catid, :affiliate))
                  AND u.active != 'Canceled'
                  AND r.name LIKE :query ORDER BY u.id
                ";
                break;
            case "paid_as_title":
                $query = '%'.$query.'%';
                $sql = "
                  WITH RECURSIVE cte AS 
                  (
                    SELECT 
                    id AS user_id,
                    1 AS `level` 
                    FROM users
                    WHERE sponsorid = :memberid
                      AND levelid = 3 
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
                          WHERE p.levelid = 3 
                  ) 
                  SELECT
                    u.id, u.fname, u.lname, u.site, u.email
                  FROM cte c 
                  JOIN users u ON u.id = c.user_id
                  LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = CURRENT_DATE()
                  JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
                  WHERE u.levelid = 3 AND u.password IS NOT NULL
                  AND EXISTS (SELECT 1 FROM categorymap WHERE u.id = userid AND FIND_IN_SET(catid, :affiliate))
                  AND u.active != 'Canceled'
                  AND r.name LIKE :query ORDER BY u.id
                ";
                break;

        }

        $affiliate = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':query', $query);
        $stmt->bindParam(':memberid', $memberid);
        $stmt->bindParam(':affiliate', $affiliate);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getWishlist($filters, $member_id)
    {
        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('oc_customer_wishlist AS w')
            ->join('oc_product as p', 'p.product_id', '=', 'w.product_id')
            ->where('w.customer_id', $member_id)
            ->selectRaw("
                p.model AS product_name,
                w.quantity
            ");

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('w.quantity', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('p.model', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("w.quantity", "desc");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id');
    }
}