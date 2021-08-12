<?php

namespace Commissions\Member;

use Illuminate\Support\Facades\DB;
use PDO;

class Rewards 
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getOrders($user_id)
    {
        $sql = "
                SELECT 
                    CONCAT(u.fname, ' ', u.lname) AS purchaser_name, t.`invoice`, DATE_FORMAT(t.`transaction_date`, '%m/%d/%Y') AS order_date, trans.description, ROUND(t.`computed_qs`, 2) AS amount
                FROM v_cm_transactions t
                JOIN transactions trans ON t.transaction_id = trans.id
                JOIN cm_hostess_program hp ON (t.`user_id` = hp.`user_id` OR t.sponsor_id = hp.`user_id`) AND hp.`is_deleted` = 0
                JOIN users u ON t.`user_id` = u.id
                WHERE t.`transaction_date` BETWEEN hp.`start_date` AND hp.`end_date`
                    AND hp.user_id = :user_id
                HAVING amount > 0;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGiftCards($filters, $user_id)
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

        // $query = DB::table('cm_rewards AS r')
        //     ->selectRaw("
        //         gc.code, 
        //         gc.amount, 
        //         gc.balance, DATE(gc.datecreated) AS earned_date, 
        //         gc.end_date AS expiration_date
        //     ")
        //     ->join('gift_cards AS gc', 'r.discount_code', '=', 'gc.code')
        //     ->where("r.user_id", $user_id);

        $query = DB::table('gift_cards AS gc')
            ->selectRaw("
                gc.code, 
                gc.amount, 
                gc.balance, DATE(gc.datecreated) AS earned_date, 
                gc.end_date AS expiration_date
            ")
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

        // apply order by
        // order by 1 column

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('gc.code', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
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

        // $query = DB::table('cm_rewards AS r')
        //     ->selectRaw("
        //         c.name AS coupon_name, 
        //         r.coupon_count, 
        //         c.date_end AS expiration_date
        //     ")
        //     ->join('oc_coupon AS c', 'r.coupon_id', '=', 'c.coupon_id')
        //     ->where("r.user_id", $user_id);

        $query = DB::table('oc_coupon AS c')
            ->selectRaw("
                c.name AS coupon_name, 
                c.uses_total AS coupon_count,
                c.date_end AS expiration_date
            ")
            ->where("c.userid", $user_id);


        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('c.uses_total', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('c.name', 'LIKE', "%{$search}%")
                    ->orWhere('c.date_end', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by
        // order by 1 column

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('c.coupon_id', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

}