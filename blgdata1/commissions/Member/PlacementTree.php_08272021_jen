<?php


namespace Commissions\Member;

use App\Mail\HoldingTankExpired;
use App\User;
use App\UserHistory;
use App\UserPlacement;
use Carbon\Carbon;
use Commissions\Console;
use Commissions\Exceptions\AlertException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDO;


class PlacementTree extends Console
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getParentDetails($user_id)
    {
        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');
        $default_affiliate = config('commission.affiliate');

        $sql = "
            SELECT
                EXISTS(
                    SELECT 1 
                    FROM cm_genealogy_placement p_
                    JOIN users u_ ON u_.id = p_.user_id
                    WHERE 
                        p_.sponsor_id = p.user_id 
                        AND u_.levelid = 3
                        AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p_.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                ) AS branch,
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS `member`,
                IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS paid_as_rank,
                
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
                u.enrolled_date,
                s.site AS `sponsor`
            FROM cm_genealogy_placement p
            JOIN users AS u ON u.id = p.user_id
            LEFT JOIN users AS s ON s.id = p.sponsor_id
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = CURRENT_DATE()
            LEFT JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            LEFT JOIN cm_ranks AS r ON r.id = dr.paid_as_rank_id            
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
        $sql = "
            SELECT
                COUNT(1)
            FROM cm_genealogy_placement p
            JOIN users u ON u.id = p.user_id
            WHERE u.levelid = 3
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                AND p.sponsor_id = :parent_id
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':parent_id', $parent_id);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function getChildren($parent_id, $offset, $take)
    {
        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');
        $default_affiliate = config('commission.affiliate');

        $sql = "
            SELECT
                EXISTS(
                    SELECT 1 
                    FROM cm_genealogy_placement p_
                    JOIN users u_ ON u_.id = p_.user_id
                    WHERE u_.levelid = 3
                        AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p_.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                        AND p_.sponsor_id = p.user_id 
                ) AS branch,
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS `member`,
                IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS paid_as_rank,
                
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
                u.enrolled_date,
                s.site AS `sponsor`
            FROM cm_genealogy_placement p
            JOIN users AS u ON u.id = p.user_id
            LEFT JOIN users AS s ON s.id = p.sponsor_id
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = CURRENT_DATE()
            LEFT JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            LEFT JOIN cm_ranks AS r ON r.id = dr.paid_as_rank_id            
            WHERE u.levelid = 3
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                AND p.sponsor_id = :parent_id 
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

    public function getUnplacedMembers($user_id)
    {
        $affiliates = config('commission.member-types.affiliates');
        $default_affiliate = config('commission.affiliate');

        $sql = "
            SELECT
                p.user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                IFNULL(r.name, '$default_affiliate') paid_as_rank,
                DATEDIFF(DATE(p.expired_at), CURRENT_DATE()) days_left,
                p.sponsor_id,
                NULL new_sponsor_id
            FROM cm_genealogy_placement p
            JOIN users u ON u.id = p.user_id
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = p.user_id AND dr.rank_date = CURRENT_DATE()
            LEFT JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
            WHERE u.levelid = 3
                AND p.sponsor_id = :sponsor_id
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                AND p.is_placed = 0
            HAVING days_left >= 1
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':sponsor_id', $user_id);

        $smt->execute();
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function placeMember($user_id, $sponsor_id, $placed_by_user_id)
    {
        if (+$user_id === +$sponsor_id) {
            throw new AlertException("Placing the member to itself is not allowed");
        }

        if ($this->isCircularSponsorship($user_id, $sponsor_id)) {
            throw new AlertException("The new sponsor is a downline of the member.");
        }

        $user = DB::transaction(function () use ($user_id, $sponsor_id, $placed_by_user_id) {
            $user = UserPlacement::findOrFail($user_id);
            $sponsor = UserPlacement::findOrFail($sponsor_id);

            $history = new UserHistory();
            $history->user_id = $user_id;
            $history->new_parent_id = $sponsor_id;
            $history->old_parent_id = $user->sponsor_id;
            $history->tree_id = UserHistory::PLACEMENT;
            $history->module_used = UserHistory::MODULE_HOLDING_TANK;
            $history->moved_by_id = $placed_by_user_id;
            $history->save();

            $user->sponsor_id = $sponsor_id;
            $user->is_placed = 1;
            $user->placed_at = Carbon::now();
            $user->save();

            return $user;
        });

        return $user;
    }

    private function isCircularSponsorship($member_id, $sponsor_id)
    {
        $sql = "
            WITH RECURSIVE cte (user_id, sponsor_id, `level`) AS (
                SELECT 
                    user_id,
                    sponsor_id,                  
                    1 AS `level`
                FROM cm_genealogy_placement
                WHERE sponsor_id = :member_id 
                
                UNION ALL
                
                SELECT
                    cgp.user_id,
                    cgp.sponsor_id,
                    `level` + 1 `level`
                FROM cm_genealogy_placement cgp
                INNER JOIN cte ON cgp.sponsor_id = cte.user_id
            )
            SELECT COUNT(1) c FROM cte WHERE cte.user_id = :sponsor_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam('member_id', $member_id);
        $stmt->bindParam('sponsor_id', $sponsor_id);
        $stmt->execute();
        return +$stmt->fetchColumn() > 0;
    }

    public function placeExpiredMember()
    {

        DB::transaction(function () {
            $affiliates = config('commission.member-types.affiliates');

            $sql = "
                INSERT INTO cm_genealogy_history (user_id, new_parent_id, old_parent_id, tree_id, module_used, moved_by_id)
                SELECT p.user_id, p.sponsor_id, p.sponsor_id, :tree_id, :module_used, 1
                FROM cm_genealogy_placement p
                WHERE DATE(p.expired_at) = CURRENT_DATE()
                    AND p.is_placed = 0
                    AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
            ";

            $tree_id = UserHistory::PLACEMENT;
            $module_used = UserHistory::MODULE_EXPIRED_HOLDING_TANK;

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':tree_id', $tree_id);
            $stmt->bindParam(':module_used', $module_used);
            $stmt->execute();

            $sql = "
                UPDATE cm_genealogy_placement p
                SET is_placed = 1, placed_at = NOW()
                WHERE DATE(p.expired_at) = CURRENT_DATE()
                    AND p.is_placed = 0
                    AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        });

    }

    public function sendNotification()
    {
        $affiliates = config('commission.member-types.affiliates');

        $placements = UserPlacement::whereRaw("
            is_placed = 0
            AND is_notified = 0
            AND DATEDIFF(expired_at, NOW()) = ?
            AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = cm_genealogy_placement.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
            AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = cm_genealogy_placement.sponsor_id AND FIND_IN_SET(cm.catid, '$affiliates'))
        ", [3])->get();

        foreach ($placements as $placement) {

            $user = $placement->user;
            $sponsor = $placement->sponsor->user;

            $this->log("Sending to Member ID {$sponsor->id} ({$sponsor->email}) for Member ID {$user->id}");

            // Mail::to($sponsor)->send(new HoldingTankExpired($placement, $user, $sponsor));
            \Commissions\Mail::send(
                $sponsor->email,
                "Unplaced Member",
                view('emails.placement.expired', compact('user', 'sponsor'))->render()
            );

            $placement->is_notified = 1;
            $placement->save();

        }
    }

}