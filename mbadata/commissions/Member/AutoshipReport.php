<?php


namespace Commissions\Member;

use Carbon\Carbon;
use Commissions\QueryHelper;
use Illuminate\Support\Facades\DB;
use \Illuminate\Database\Capsule\Manager;
use \Illuminate\Support\Facades\Config;
use Commissions\CsvReport;
use PDO;

class AutoshipReport
{

    const REPORT_PATH = "csv/member/autoship";

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getCarbonDate($year_month)
    {
        try {
            $date = Carbon::createFromFormat("Y-m", $year_month);

            if($date->format("Y-m-d") > Carbon::today()->format("Y-m-d")) {
                $date = $date->startOfMonth();
            } else if($date->format("Y-m-d") < Carbon::today()->format("Y-m-d")) {
                $date = $date->endOfMonth();
            }

            return $date;
        }
        catch (\Exception $ex) {
            return false;
        }
    }

    protected function isUnderMember($member_id, $column, $depth = null)
    {
        $member_id = +$member_id;

        return "
            EXISTS (
                WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS level
                    FROM users
                    WHERE sponsorid = $member_id
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.level + 1 level
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE " .  ($depth !== null ? "downline.level < $depth" : "true" ) . "
                )
                SELECT * FROM downline d WHERE d.user_id = $column
            )
        ";
    }

    public function getPendingAutoshipAmount($year_month, $member_id = null)
    {
        return $this->getPendingAutoshipQuery($year_month, $member_id)->sum("oa.sub_total");
    }

    public function getSuccessfulAutoshipAmount($year_month, $member_id = null)
    {
        return $this->getSuccessfulAutoshipQuery($year_month, $member_id)->sum("t.sub_total");
    }

    public function getFailedAutoshipAmount($year_month, $member_id = null)
    {
        return $this->getFailedAutoshipQuery($year_month, $member_id)->sum("t.sub_total");
    }

    public function getMembersCount($year_month, $member_id = null)
    {
        return $this->getMembersQuery($year_month, $member_id)->count(DB::raw("1"));
    }

    public function getActiveMembersOnAutoshipCount($year_month, $member_id = null)
    {
        return $this->getActiveMembersOnAutoshipQuery($year_month, $member_id)->count(DB::raw("1"));
    }

    public function getCancelledAutoshipCount($year_month, $member_id = null)
    {
        return $this->getCancelledAutoshipQuery($year_month, $member_id)->count(DB::raw("1"));
    }

    public function getAverageOrderValue($year_month, $member_id = null)
    {
        $date = $this->getCarbonDate($year_month);

        $start_date = $date->copy()->startOfMonth()->format("Y-m-d");
        $end_date = $date->copy()->endOfMonth()->format("Y-m-d");

        $query = DB::table("v_cm_transactions AS t")
            ->join("users AS u", "u.id", "=", "t.user_id")
            ->where("t.is_autoship", 1)
            ->selectRaw("
                ROUND(SUM(t.amount)/COUNT(1), 2) amount
            ")
            ->where("t.type", 'product')
            ->where("u.levelid", 3)
            ->whereBetween("t.transaction_date", [$start_date, $end_date])
            ->whereRaw(QueryHelper::NotExistsUnderBen('t.user_id'));

        if($member_id !== null) {
            $query->whereRaw($this->isUnderMember($member_id, "t.user_id"));
        }

        $result = $query->first();

        if($result === null) return 0;

        return $result->amount;
    }

    protected function getPendingAutoshipQuery($year_month, $member_id = null)
    {

        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $default_affiliate = config('commission.affiliate');

        $date = $this->getCarbonDate($year_month);

        $start_date = $date->copy()->format("Y-m-d");
        $end_date = $date->copy()->endOfMonth()->format("Y-m-d");

        $query =  DB::table('oc_autoship AS oa')
            ->selectRaw("
                oa.id AS autoship_id,
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates')), '$default_affiliate', 'Customer') account_type,
                oa.sub_total AS price,
                0 AS cv,
                DATE(oa.nextdeliverydate) AS processing_date
            ")
            ->join("users AS u", "u.id", "=", "oa.customer_id")
            ->join("users AS s", "s.id", "=", "u.sponsorid")
            ->where('oa.is_active', 1)
            ->where("u.active", "Yes")
            ->where("u.levelid", 3)
            ->whereRaw("DATE(oa.nextdeliverydate) BETWEEN ? AND ? ", [$start_date, $end_date])
            ->whereRaw(QueryHelper::NotExistsUnderBen('oa.customer_id'))
        ;

        if($member_id !== null) {
            $query->whereRaw($this->isUnderMember($member_id, "oa.customer_id"));
        }

        return $query;
    }

    protected function getSuccessfulAutoshipQuery($year_month, $member_id = null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $default_affiliate = config('commission.affiliate');

        $date = $this->getCarbonDate($year_month);

        $start_date = $date->copy()->startOfMonth()->format("Y-m-d");
        $end_date = $date->copy()->endOfMonth()->format("Y-m-d");

        $query = DB::table('transactions AS t')
            ->selectRaw("
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates')), '$default_affiliate', 'Customer') account_type,
                t.sub_total AS price,
                getCappedVolume(tt.user_id, tt.transaction_id, tt.transaction_date) AS cv,
                tt.transaction_date AS processing_date
            ")
            ->join("users AS u", "u.id", "=", "t.userid")
            ->join("users AS s", "s.id", "=", "t.sponsorid")
            ->join("v_cm_transactions AS tt", "tt.transaction_id", "=", "t.id")
            ->where('t.status', 'Approved')
            ->where('t.is_autoship', 1)
            ->where('t.type', 'product')
            ->where("u.levelid", 3)
            ->whereBetween("tt.transaction_date", [$start_date, $end_date])
            ->whereRaw(QueryHelper::NotExistsUnderBen('t.userid'));

        if($member_id !== null) {
            $query->whereRaw($this->isUnderMember($member_id, "t.userid"));
        }

        return $query;
    }

    protected function getFailedAutoshipQuery($year_month, $member_id = null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $default_affiliate = config('commission.affiliate');

        $date = $this->getCarbonDate($year_month);

        $query =  DB::table('transactions AS t')
            ->selectRaw("
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates')), '$default_affiliate', 'Customer') account_type,
                t.sub_total AS price,
                IF(getVolume(t.id) > 200, 200, getVolume(t.id)) AS cv,
                DATE(t.transactiondate) AS processing_date
            ")
            ->join('users AS u' , 't.userid', '=' , 'u.id')
            ->join("users AS s", "s.id", "=", "t.sponsorid")
            ->where('t.is_autoship', 1)
            //->whereRaw("t.status != 'Approved'")
            ->where('t.status', "Failed")
            ->where('t.type', 'product')
            ->where('u.levelid' , 3)
            ->whereRaw("YEAR(t.transactiondate) = $date->year AND MONTH(t.transactiondate) = $date->month")
            ->whereRaw("NOT EXISTS(
                SELECT 1 
                FROM v_cm_transactions tt 
                WHERE tt.user_id = t.userid
                    AND tt.type = 'product'
                    AND tt.is_autoship = 1
                    AND YEAR(tt.transaction_date) = $date->year AND MONTH(tt.transaction_date) = $date->month 
            )")
            ->whereRaw(QueryHelper::NotExistsUnderBen('t.userid'));

        if($member_id !== null) {
            $query->whereRaw($this->isUnderMember($member_id, "t.userid"));
        }

        return $query;
    }

    protected function getMembersQuery($year_month, $member_id = null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');

        $date = $this->getCarbonDate($year_month);

        $query = DB::table("users AS u")
            ->where("u.enrolled_date", "<=", $date->format("Y-m-d"))
            ->where("u.levelid", 3)
            ->whereRaw("EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates,$customers'))")
            ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'));


        if($member_id !== null) {
            $query->whereRaw($this->isUnderMember($member_id, "u.id"));
        }

        return $query;
    }

    protected function getActiveMembersOnAutoshipQuery_old($year_month, $member_id)
    {
        $date = $this->getCarbonDate($year_month);

        $start_date = $date->copy()->startOfMonth()->format("Y-m-d");
        $end_date = $date->copy()->endOfMonth()->format("Y-m-d");

        $query = DB::table(DB::raw("(
            SELECT 
                t.user_id
            FROM v_cm_transactions t
            WHERE t.is_autoship = 1 AND t.type = 'product'
                AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND " . ($member_id !== null ? $this->isUnderMember($member_id, 't.user_id') : "true")  . "
                AND " . QueryHelper::NotExistsUnderBen('t.user_id') . "
                
            UNION
            
            SELECT
                oa.customer_id AS user_id
            FROM oc_autoship oa
            JOIN users u ON u.id = oa.customer_id
            WHERE oa.is_active = 1
                AND u.active = 'Yes'
                AND u.levelid = 3
                AND DATE(oa.nextdeliverydate) BETWEEN '$start_date' AND '$end_date'
                AND " . ($member_id !== null ? $this->isUnderMember($member_id, 'u.id') : "true")  . "
                AND " . QueryHelper::NotExistsUnderBen('u.id') . "
        ) a"));

        return $query;
    }

    protected function getActiveMembersOnAutoshipQuery($year_month, $member_id = null)
    {

        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $default_affiliate = config('commission.affiliate');

        $date = $this->getCarbonDate($year_month);
        $is_current_month = $date->isCurrentMonth(true);

        $start_date = $date->copy()->format("Y-m-d");
        $end_date = $date->copy()->endOfMonth()->format("Y-m-d");

        $query =  DB::table('users AS u')
            ->selectRaw("
                oa.id AS autoship_id,
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                IF(t.purchaser_catid IS NULL,
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates')), '$default_affiliate', 'Customer'),
                    IF(FIND_IN_SET(t.purchaser_catid, '$affiliates'), '$default_affiliate', 'Customer')
                ) account_type,
                IFNULL(t.sub_total, oa.sub_total) AS price,
                IFNULL(t.computed_cv, 0) AS cv,
                IFNULL(t.transaction_date, DATE(oa.nextdeliverydate)) AS processing_date
            ")
            ->join("oc_autoship AS oa", "oa.customer_id", "=", "u.id")
            ->join("users AS s", "s.id", "=", "u.sponsorid")
            ->leftJoin("v_cm_transactions AS t", function($join) use ($start_date, $end_date) {
                $join->on("t.user_id", "=", "u.id");
                $join->whereRaw("t.transaction_id = (
                    SELECT _t.transaction_id FROM v_cm_transactions _t
                    WHERE
                        _t.user_id = u.id
                        AND _t.transaction_date BETWEEN '$start_date' AND '$end_date'
                        AND _t.is_autoship = 1
                    ORDER BY _t.transaction_date DESC
                    LIMIT 1
                )");
            })
            ->where('oa.is_active', 1)
            //->where("u.active", "Yes")
            ->where("u.levelid", 3)
            ->where('u.enrolled_date', '<=', $end_date)
            ->whereRaw(QueryHelper::NotExistsUnderBen('oa.customer_id'))
        ;

        if($is_current_month) {
            $query->whereRaw("(oa.is_active = 1 OR t.is_autoship = 1)");
        } else {
            $query->whereRaw("t.is_autoship = 1");
        }

        if($member_id !== null) {
            $query->whereRaw($this->isUnderMember($member_id, "oa.customer_id"));
        }

        return $query;
    }

    protected function getCancelledAutoshipQuery($year_month, $member_id = null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $default_affiliate = config('commission.affiliate');

        $date = $this->getCarbonDate($year_month);

        $start_date = $date->startOfMonth()->format("Y-m-d");
        $end_date = $date->endOfMonth()->format("Y-m-d");

        $query = DB::table("cm_autoship_history AS ah")
            ->selectRaw("
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates')), '$default_affiliate', 'Customer') account_type,
                ah.amount AS price,
                0 AS cv,
                ah.delivery_date AS processing_date
            ")
            ->join("users AS u", "u.id", "=", "ah.user_id")
            ->join("users AS s", "s.id", "=", "u.sponsorid")
            ->whereBetween("ah.delivery_date", [$start_date, $end_date])
            ->where("status", "CANCELED")
            ->where("u.levelid", 3)
            ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'));

        if($member_id !== null) {
            $query->whereRaw($this->isUnderMember($member_id, "u.id"));
        }

        return $query;
    }

    public function getPersonallyEnrolledRetentionRate($year_month, $member_id)
    {
        $date = $this->getCarbonDate($year_month);

        $start_date = $date->copy()->startOfMonth()->format("Y-m-d");
        $end_date = $date->copy()->endOfMonth()->format("Y-m-d");

        $previous = $date->copy()->startOfMonth()->subMonth();

        $previous_start_date = $previous->copy()->startOfMonth()->format("Y-m-d");
        $previous_end_date = $previous->copy()->endOfMonth()->format("Y-m-d");

        $previous = DB::table("v_cm_transactions AS t")
            ->join("users AS u", "u.id", "=", "t.user_id")
            ->whereBetween("t.transaction_date", [$previous_start_date, $previous_end_date])
            ->where("t.type", "product")
            ->where("t.sponsor_id", $member_id)
            ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'));

        $previous_users = $previous->select(DB::raw("DISTINCT t.user_id"))->get();

        $previous_count = $previous_users->count();

        $current_count = DB::table("v_cm_transactions AS t")
            ->join("users AS u", "u.id", "=", "t.user_id")
            ->whereBetween("t.transaction_date", [$start_date, $end_date])
            ->where("t.type", "product")
            ->where("t.sponsor_id", $member_id)
            ->whereIn("t.user_id", $previous_users->pluck('user_id')->toArray())
            ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'))
            ->count(DB::raw("DISTINCT t.user_id"));

        return $previous_count === 0 ? 0 : ($current_count / $previous_count) * 100;
    }

    public function getOrganizationalRetentionRate($year_month, $member_id)
    {
        $date = $this->getCarbonDate($year_month);

        $start_date = $date->copy()->startOfMonth()->format("Y-m-d");
        $end_date = $date->copy()->endOfMonth()->format("Y-m-d");

        $previous = $date->copy()->startOfMonth()->subMonth();

        $previous_start_date = $previous->copy()->startOfMonth()->format("Y-m-d");
        $previous_end_date = $previous->copy()->endOfMonth()->format("Y-m-d");

        $previous = DB::table("v_cm_transactions AS t")
            ->join("users AS u", "u.id", "=", "t.user_id")
            ->whereBetween("t.transaction_date", [$previous_start_date, $previous_end_date])
            ->where("t.type", "product")
            ->whereRaw($this->isUnderMember($member_id, "u.id"), 5)
            ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'));

        $previous_users = $previous->select(DB::raw("DISTINCT t.user_id"))->get();

        $previous_count = $previous_users->count();

        $current_count = DB::table("v_cm_transactions AS t")
            ->join("users AS u", "u.id", "=", "t.user_id")
            ->whereBetween("t.transaction_date", [$start_date, $end_date])
            ->where("t.type", "product")
            ->whereRaw($this->isUnderMember($member_id, "u.id"), 5)
            ->whereIn("t.user_id", $previous_users->pluck('user_id')->toArray())
            ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'))
            ->count(DB::raw("DISTINCT t.user_id"));

        return $previous_count === 0 ? 0 : ($current_count / $previous_count) * 100;
    }

    public function getPendingAutoship($filters, $member_id = null)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $year_month = isset($filters['year_month']) ? $filters['year_month'] : null;

        if (!$year_month) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
        }

        $query = $this->getPendingAutoshipQuery($year_month, $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('oa.nextdeliverydate', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("oa.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
    }

    public function getSuccessfulAutoship($filters, $member_id = null)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $year_month = isset($filters['year_month']) ? $filters['year_month'] : null;

        if (!$year_month) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
        }

        $query = $this->getSuccessfulAutoshipQuery($year_month, $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('tt.transaction_date', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("t.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
    }

    public function getFailedAutoship($filters, $member_id = null)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $year_month = isset($filters['year_month']) ? $filters['year_month'] : null;

        if (!$year_month) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
        }

        $query = $this->getFailedAutoshipQuery($year_month, $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('t.transactiondate', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("t.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
    }

    public function getCancelledAutoship($filters, $member_id = null)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $year_month = isset($filters['year_month']) ? $filters['year_month'] : null;

        if (!$year_month) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
        }

        $query = $this->getCancelledAutoshipQuery($year_month, $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('ah.delivery_date', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("ah.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
    }

    public function getActiveMembersOnAutoship($filters, $member_id = null)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $year_month = isset($filters['year_month']) ? $filters['year_month'] : null;

        if (!$year_month) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
        }

        $query = $this->getActiveMembersOnAutoshipQuery($year_month, $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
//                    ->orWhere('oa.nextdeliverydate', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("oa.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'year_month');
    }

    public function getLinkCsvActiveMembersOnAutoship($filters, $member_id = null)
    {
        $csv = new CsvReport(static::REPORT_PATH);

        $year_month = isset($filters['year_month']) ? $filters['year_month'] : null;
        $data       = $this->getActiveMembersOnAutoshipQuery($year_month, $member_id)->get();

        $filename = "active-member-autoship-$member_id-";
        $filename .= time();

        return $csv->generateLink($filename, $data);
    }

    public function getLinkCsvPendingAutoship($filters, $member_id = null)
    {
        $csv = new CsvReport(static::REPORT_PATH);

        $year_month = isset($filters['year_month']) ? $filters['year_month'] : null;
        $data       = $this->getPendingAutoshipQuery($year_month, $member_id)->get();
        
        $filename = "pending-autoship-$member_id-";
        $filename .= time();

        return $csv->generateLink($filename, $data);
    }
}


