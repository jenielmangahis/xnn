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

    public function getParentDetails($user_id)
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
                IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS paid_as_rank,
                IFNULL(c.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS current_rank,
                IFNULL(a.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS highest_rank,
                
                IFNULL(ROUND(dv.coach_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS coach_points,
                IFNULL(ROUND(dv.referral_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS referral_points,
                IFNULL(ROUND(dv.organization_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS organization_points,
                IFNULL(ROUND(dv.team_group_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS team_group_points,
                
                EXISTS(
                    SELECT 1
                    FROM v_cm_transactions t 
                    WHERE t.user_id = u.id 
                        AND t.type = 'product'
                        AND t.transaction_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()
                ) has_order_last_30_days,
                DATE_FORMAT(u.created, '%Y-%m-%d') enrolled_date,
                s.site AS `sponsor`
            FROM users AS u
            LEFT JOIN users AS s ON s.id = u.sponsorid
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = CURRENT_DATE()
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
            WHERE 
                u.id = :id
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':id', $user_id);
        $smt->execute();
        return $smt->fetch(PDO::FETCH_ASSOC);
    }

    public function getChildrenPaginate($parent_id, $page_no = 0)
    {
        if ($page_no == 0) {
            $page_no = 1;
        }

        $no_of_records_per_page = 20;
        $offset = ($page_no - 1) * $no_of_records_per_page;

        $total_count = $this->getChildrenCount($parent_id);

        $total_pages = ceil($total_count / $no_of_records_per_page);

        $results = $this->getChildren($parent_id, $offset, $no_of_records_per_page);
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
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates,$customers'))
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':parent_id', $parent_id);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function getChildren($parent_id, $offset, $take)
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
                        AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = c.id AND FIND_IN_SET(cm.catid, '$affiliates,$customers'))
                ) AS branch,
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS `member`,
                IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS paid_as_rank,
                IFNULL(c.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS current_rank,
                IFNULL(a.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS highest_rank,
                IFNULL(ROUND(dv.coach_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS coach_points,
                IFNULL(ROUND(dv.referral_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS referral_points,
                IFNULL(ROUND(dv.organization_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS organization_points,
                IFNULL(ROUND(dv.team_group_points, 2), IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'N/A', 0)) AS team_group_points,
                
                EXISTS(
                    SELECT 1
                    FROM v_cm_transactions t 
                    WHERE t.user_id = u.id 
                        AND t.type = 'product'
                        AND t.transaction_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()
                ) has_order_last_30_days,
                DATE_FORMAT(u.created, '%Y-%m-%d') enrolled_date,
                s.site AS `sponsor`
            FROM users AS u
            LEFT JOIN users AS s ON s.id = u.sponsorid
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = CURRENT_DATE()
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
            WHERE u.sponsorid = :parent_id AND u.levelid = 3
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates,$customers'))
            ORDER BY 
                IFNULL(dv.organization_points, 0) DESC,
                IFNULL(dv.coach_points, 0) DESC,
                IFNULL(dr.paid_as_rank_id, 0) DESC,
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
}