<?php


namespace Commissions\Member;

use Illuminate\Support\Facades\Config;
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
                IF(u.active = 'Canceled', 'Terminated Associate', CONCAT(u.fname, ' ', u.lname)) AS `member`,
                IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS paid_as_rank,
                IF(u.active = 'Canceled','N/A',IFNULL(c.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate'))) AS current_rank,
                IFNULL(a.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS highest_rank,
                
                IFNULL(ROUND(dv.pea, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS pea,
                IFNULL(ROUND(dv.ta - dv.pea, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS ta,
                IFNULL(ROUND(dv.mar, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS mar,
                IFNULL(ROUND(dv.qta, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS qta,                
                
                EXISTS(
                    SELECT 1
                    FROM v_cm_transactions t 
                    WHERE t.user_id = u.id 
                        AND t.type = 'product'
                        AND t.transaction_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()
                ) has_order_last_30_days,
                DATE_FORMAT(ca.affiliated_date, '%Y-%m-%d') enrolled_date,
                s.site AS `sponsor`
            FROM users AS u
            LEFT JOIN users AS s ON s.id = u.sponsorid
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = '$start_date'
            LEFT JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            LEFT JOIN cm_ranks AS r ON r.id = dr.paid_as_rank_id
            LEFT JOIN cm_ranks AS c ON c.id = dr.rank_id
            LEFT JOIN cm_achieved_ranks ac ON ac.user_id = u.id AND ac.rank_id = (
                SELECT aac.rank_id
                FROM cm_achieved_ranks aac
                WHERE aac.user_id = ac.user_id
                ORDER BY aac.rank_id DESC LIMIT 1
            )
            LEFT JOIN cm_ranks a ON a.id = ac.rank_id
			LEFT JOIN cm_affiliates ca ON ca.user_id = u.id
            WHERE 
                u.id = :id
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
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates'))
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
                IF(u.active = 'Canceled', 'Terminated Associate', CONCAT(u.fname, ' ', u.lname)) AS `member`,
                IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS paid_as_rank,
                IF(u.active = 'Canceled','N/A',IFNULL(c.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate'))) AS current_rank,
                IFNULL(a.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS highest_rank,
                
                IFNULL(ROUND(dv.pea, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS pea,
                IFNULL(ROUND(dv.ta - dv.pea, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS ta,
                IFNULL(ROUND(dv.mar, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS mar,
                IFNULL(ROUND(dv.qta, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS qta,
                
                EXISTS(
                    SELECT 1
                    FROM v_cm_transactions t 
                    WHERE t.user_id = u.id 
                        AND t.type = 'product'
                        AND t.transaction_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()
                ) has_order_last_30_days,
                DATE_FORMAT(ca.affiliated_date, '%Y-%m-%d') enrolled_date,
                s.site AS `sponsor`
            FROM users AS u
            LEFT JOIN users AS s ON s.id = u.sponsorid
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = '$start_date'
            LEFT JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            LEFT JOIN cm_ranks AS r ON r.id = dr.paid_as_rank_id
            LEFT JOIN cm_ranks AS c ON c.id = dr.rank_id
            LEFT JOIN cm_achieved_ranks ac ON ac.user_id = u.id AND ac.rank_id = (
                SELECT aac.rank_id
                FROM cm_achieved_ranks aac
                WHERE aac.user_id = ac.user_id
                ORDER BY aac.rank_id DESC LIMIT 1
            )
            LEFT JOIN cm_ranks a ON a.id = ac.rank_id
			LEFT JOIN cm_affiliates ca ON ca.user_id = u.id
            WHERE u.sponsorid = :parent_id AND u.levelid = 3
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates'))
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

    public function getUsers_v1($filters, $member_id)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;
        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        // custom filters
        $memberId = $filters['user_id'];

        $query = DB::table('cm_energy_accounts AS s')
            ->selectRaw("
                s.customer_id,
                CONCAT(u.fname,' ',u.lname) AS customer_name,
                CONCAT(u.fname,' ',LEFT(u.lname, 1), '.') AS customer_name_redacted,
                IF(cea.current_status = 4, DATE(cea.created_at),'N/A') AS date_accepted,	
                IF(cea.current_status = 5, DATE(cea.created_at),'N/A') AS date_flowing,
                st.display_text as status
            ")
            ->leftJoin('cm_energy_account_logs AS cea', 'cea.energy_account_id','=','s.id')
            ->leftJoin('cm_energy_account_status_types AS st', 'st.id', '=','s.status')
            ->join('customers AS u', 'u.id','=','s.customer_id')
            ->whereRaw("FIND_IN_SET(s.status,'4,5,6') AND EXISTS(SELECT 1 FROM cm_daily_volumes dv WHERE dv.user_id = s.sponsor_id AND dv.pea > 0 AND dv.volume_date = CURRENT_DATE())")
            ->where('s.sponsor_id', $memberId);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        if (isset($search) && $search['value'] != '') {
            $value = trim($search['value']);
            $query =
                $query->where(function ($query) use ($value) {
                    $query->where('st.display_text', 'LIKE', "%{$value}%")
                        ->orWhereRaw("DATE_FORMAT(cea.created_at, '%Y-%m-%d') LIKE ?", ["%{$value}%"])
                        ->orWhereRaw("CONCAT(u.fname,' ',u.lname) LIKE ?", ["%{$value}%"]);
                });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

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
                          ON p.sponsorid = cte.user_id) 
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
                          ON p.sponsorid = cte.user_id) 
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
                          ON p.sponsorid = cte.user_id) 
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
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
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
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
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
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
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
                    
                    UNION ALL 
                    
                    SELECT 
                    p.id AS user_id,
                    cte.`level` + 1 `level` 
                    FROM users p
                    INNER JOIN cte 
                      ON p.sponsorid = cte.user_id
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

        $affiliate = Config::get('commission.member-types.affiliates');
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':query', $query);
        $stmt->bindParam(':memberid', $memberid);
        $stmt->bindParam(':affiliate', $affiliate);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getUsers($filters, $member_id)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;
        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        // custom filters
        $memberId = $filters['user_id'];

        $query = DB::table('cm_energy_accounts AS s')
            ->selectRaw("
				s.id,
                IF(IFNULL(u.fname, '') <> '', CONCAT(IFNULL(u.fname, ''), ' ', LEFT(IFNULL(u.lname, ''), 1), '.'), CONCAT(SUBSTR(u.business, 1, 5),'***')) AS customer_name,
                IF(IFNULL(u.fname, '') <> '', CONCAT(IFNULL(u.fname, ''), ' ', LEFT(IFNULL(u.lname, ''), 1), '.'), CONCAT(SUBSTR(u.business, 1, 5),'***')) AS customer_name_redacted,
				IFNULL((SELECT created_at FROM cm_energy_account_logs WHERE energy_account_id = s.`id` AND current_status = 4 ORDER BY created_at DESC LIMIT 1),'N/A') AS date_accepted,
				IFNULL((SELECT created_at FROM cm_energy_account_logs WHERE energy_account_id = s.`id` AND current_status = 5 ORDER BY created_at DESC LIMIT 1),'N/A') AS date_flowing,
                st.display_text as status,
                u.id AS customer_id
            ")
            ->leftJoin('cm_energy_account_status_types AS st', 'st.id', '=','s.status')
            ->join('customers AS u', 'u.id','=','s.customer_id')
            ->whereRaw("EXISTS (SELECT
							1
						FROM cm_energy_account_logs l
						WHERE l.energy_account_id = s.id
						AND FIND_IN_SET(l.current_status, '4,5,6')
						AND l.created_date <= CURRENT_DATE())
						AND NOT EXISTS (SELECT
							1
						FROM cm_energy_account_logs l
						WHERE l.energy_account_id = s.id
						AND FIND_IN_SET(l.current_status, '7')
						AND l.created_date <= CURRENT_DATE())")
            ->where('s.sponsor_id', $memberId);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        if (isset($search) && $search['value'] != '') {
            $value = trim($search['value']);
            $query =
                $query->where(function ($query) use ($value) {
                    $query->where('st.display_text', 'LIKE', "%{$value}%")
                        ->orWhereRaw("DATE_FORMAT(cea.created_at, '%Y-%m-%d') LIKE ?", ["%{$value}%"])
                        ->orWhereRaw("CONCAT(u.fname,' ',u.lname) LIKE ?", ["%{$value}%"]);
                });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id');
    }
}