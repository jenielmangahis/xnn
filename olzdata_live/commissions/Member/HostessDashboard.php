<?php

namespace Commissions\Member;

use Illuminate\Support\Facades\DB;
use PDO;

class HostessDashboard
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getSharingLink($user_id) 
    {
        $sql = "
            SELECT 
                hp.social_link_shorten AS link
            FROM cm_hostess_program hp 
            WHERE hp.is_deleted = 0 AND CURRENT_DATE() BETWEEN hp.start_date AND hp.end_date
                AND hp.user_id = :user_id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getCountdown($user_id) 
    {
        $sql = "
            SELECT 
                hp.end_date
            FROM cm_hostess_program hp 
            WHERE hp.is_deleted = 0 AND CURRENT_DATE() BETWEEN hp.start_date AND hp.end_date
                AND hp.user_id = :user_id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $countdown_date = $stmt->fetch(PDO::FETCH_ASSOC);

        return !empty($countdown_date) ? date("m/d/Y H:i:s", strtotime($countdown_date['end_date'] . '23:59:59')) : 0;
    }

    public function getAmount($user_id) 
    {
        $sql = "
            SELECT 
                IFNULL(SUM(t.`computed_cv`), 0) AS total_sales
            FROM v_cm_transactions t
            JOIN cm_hostess_program hp ON (t.`user_id` = hp.`user_id` OR t.sponsor_id = hp.`user_id`)
            WHERE t.`transaction_date` BETWEEN hp.`start_date` AND hp.`end_date` 
                AND hp.is_deleted = 0
                AND hp.user_id = :user_id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrders($id)
    {
        $customers = config('commission.member-types.customers');

        $sql = "
            SELECT 
                CONCAT(u.fname, ' ', u.lname) AS customer, t.transaction_id AS order_id, p.sku AS description, t.`computed_cv` AS amount
            FROM cm_rewards r
            JOIN cm_hostess_program hp ON hp.id = r.`hostess_program_id`
            JOIN v_cm_transactions t ON (t.`user_id` = hp.`user_id` OR t.sponsor_id = hp.`user_id`) AND t.`transaction_date` BETWEEN hp.`start_date` AND hp.`end_date`
            JOIN users u ON u.id = t.`user_id`
            LEFT JOIN oc_product p ON p.`product_id` = t.`item_id`
            WHERE FIND_IN_SET(t.`purchaser_catid`, :customers)
                AND r.id = :id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":customers", $customers);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOpenEvent($filters, $user_id)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;

        // default filters
        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];
        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('cm_hostess_program AS hp')
            ->selectRaw("
                CONCAT(u.fname, ' ', u.lname) AS customer,
                t.invoice, 
                t.transaction_date AS order_date,
                p.model AS description,
                t.computed_cv AS amount
            ")
            ->leftjoin('v_cm_transactions AS t', function($join) {
                $join->whereRaw("(t.user_id = hp.user_id OR t.sponsor_id = hp.user_id)")
                    ->whereRaw("t.transaction_date BETWEEN hp.start_date AND hp.end_date");
            })
            ->join('users AS u', 'u.id', '=', 't.user_id')
            ->leftjoin('oc_product AS p', 'p.product_id', '=', 't.item_id')
            ->where("hp.is_deleted", 0)
            ->whereRaw("CURRENT_DATE() BETWEEN hp.start_date AND hp.end_date")
            ->where("hp.user_id", $user_id);


        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('t.computed_cv', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('t.transaction_date', 'LIKE', "%{$search}%")
                    ->orWhere('p.model', $search)
                    ->orWhere('t.invoice', $search);
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('t.transaction_date', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }
        
        $q = $query->toSql();
        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'q');
    }

    public function getDailyRewards($filters, $user_id)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;

        // default filters
        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];
        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('cm_rewards AS r')
            ->selectRaw("
                r.date_created AS date, 
                r.total_sales, 
                r.discount AS product_credits,
                r.id AS rewards_id
            ")
            ->where("r.user_id", $user_id);


        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('r.total_sales', $search)
                    ->orWhere('gc.amount', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('r.date_created', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('r.date_created', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getProductCredits($filters, $user_id)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;

        // default filters
        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];
        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('cm_rewards AS r')
            ->selectRaw("
                gc.code, 
                gc.validationcode AS validation_code, 
                CONCAT(DATE_FORMAT(hp.start_date, '%m/%d/%Y'), ' to ', DATE_FORMAT(hp.end_date, '%m/%d/%Y')) AS period_earned, 
                gc.amount, 
                gc.balance, 
                gc.end_date AS expiration_date
            ")
            ->join("gift_cards AS gc", "r.discount_code", "=", "gc.code")
            ->join("cm_hostess_program AS hp", "hp.id", "=", "r.hostess_program_id")
            ->where("gc.name", "HOSTESS Rewards")
            ->where("gc.userid", $user_id);


        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('gc.code', $search)
                    ->orWhere('gc.amount', $search)
                    ->orWhere('gc.balance', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('gc.end_date', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('gc.end_date', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $q = $query->toSql();
        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'q');
    }

    public function getCoupons($filters, $user_id)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;

        // default filters
        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];
        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('reward_discount AS rd')
            ->selectRaw("
                rd.id AS code, 
                CONCAT(DATE_FORMAT(hp.start_date, '%m/%d/%Y'), ' to ', DATE_FORMAT(hp.end_date, '%m/%d/%Y')) AS period_earned,
                '50% Off A Single Item' AS description,
                IF(rd.discount_count > 0, 'Available', 'Used') AS status
            ")
            ->join('cm_rewards AS r', 'r.no_fifty_off', '=', 'rd.id')
            ->join('cm_hostess_program AS hp', 'hp.id', '=', 'r.hostess_program_id')
            ->where("r.user_id", $user_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('rd.id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('rd.date_created', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('rd.date_created', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $q = $query->toSql();
        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'q');
    }

}