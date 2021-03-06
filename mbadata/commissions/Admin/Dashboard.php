<?php

namespace Commissions\Admin;
use Carbon\Carbon;
use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;
use \PDO;
use Commissions\QueryHelper;
use \Illuminate\Database\Capsule\Manager;
use \Illuminate\Support\Facades\Config;

class Dashboard
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getNewCustomerCount($start_date, $end_date)
    {
        return $this->getNewCustomersQuery($start_date,$end_date)->count();
//        $sql = "
//            SELECT
//                COUNT(1) customer_count
//            FROM cm_customers c
//            JOIN users u on u.id = c.user_id
//            JOIN v_cm_transactions t ON t.user_id = c.user_id AND t.transaction_date = c.enrolled_date
//            WHERE c.enrolled_date BETWEEN :start_date AND :end_date
//                AND FIND_IN_SET(c.cat_id,:customers)
//                AND u.active = 'Yes'
//                AND " . QueryHelper::NotExistsUnderBen('c.user_id') . "
//                AND u.id " . $this->excludeTestUsers() . "
//        ";
//
//        $customers = config('commission.member-types.customers');
//
//        $stmt = $this->db->prepare($sql);
//        $stmt->bindParam(":start_date", $start_date);
//        $stmt->bindParam(":end_date", $end_date);
//        $stmt->bindParam(":customers", $customers);
//        $stmt->execute();
//
//        return $stmt->fetchColumn();
    }

    public function getNewCustomers($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];


        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = $this->getNewCustomersQuery($start_date,$end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhere('vct.transaction_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.fname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.lname', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(u.fname, ' ', u.lname) LIKE ?", ['%'.$search.'%'])
                    ->orWhereRaw("CONCAT(ul.fname, ' ', ul.lname) LIKE ?", ['%'.$search.'%']);

                if(stripos('customer', $search) !== false) {
                    $query->orWhere("vct.sponsor_catid", 13);
                }
                if(stripos('affiliate', $search) !== false) {
                    $query->orWhereRaw("FIND_IN_SET(vct.sponsor_catid,'14,15')");
                }
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getNewCustomersQuery($start_date, $end_date)
    {
        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.ibo');

        $query = DB::table('cm_customers AS cc')
            ->selectRaw("cc.user_id AS member_id, 
                  CONCAT(u.fname,' ', u.lname) AS member, 
                  ul.id sponsor_id, 
                  CONCAT(ul.fname,' ', ul.lname) AS sponsor,
                  IF(FIND_IN_SET(vct.sponsor_catid, '$affiliates'), 'Affiliate', 'Customer') AS sponsor_type,
                  IFNULL(vct.transaction_id, 'NO ORDERS YET') order_id,
                  getCommissionValue(vct.transaction_id) AS cv,
                  IFNULL(vct.amount, 0) AS amount_paid,
                  IF(oa.is_active = 1,'Y','N') has_subscription,
                  IFNULL(u.cellphone, u.dayphone) AS cellphone")
            ->join('users AS u', 'u.id', '=', 'cc.user_id')
            ->join('users AS ul', 'ul.id', '=', 'u.sponsorid')
            ->leftJoin('oc_autoship AS oa', 'oa.customer_id', '=', 'u.id')
            ->leftJoin("v_cm_transactions AS vct", function($join) {
                        $join->on('vct.user_id', '=', 'cc.user_id');
                        $join->whereRaw('vct.transaction_id = (
                        SELECT
                        vct1.transaction_id
                      FROM v_cm_transactions vct1
                      WHERE vct1.user_id = cc.user_id
                      ORDER BY vct1.transaction_id ASC LIMIT 1)');
            })
            ->whereRaw("cc.enrolled_date BETWEEN '$start_date' AND '$end_date'
                AND vct.purchaser_catid = $customers AND u.active = 'Yes' AND u.id " . $this->excludeTestUsers())
            ->whereRaw(QueryHelper::NotExistsUnderBen('cc.user_id'));

        return $query;
    }

    public function getNewCustomerWithProductSubscriptionCount($start_date, $end_date)
    {
return $this->getNewCustomersWithProductSubscriptionQuery($start_date,$end_date)->count();
//        $sql = "
//             SELECT
//                COUNT(1) customer_count
//            FROM cm_customers c
//            JOIN users u on u.id = c.user_id
//            JOIN oc_autoship oa ON oa.customer_id = c.user_id AND oa.is_active = 1 AND DATE(oa.purchasedate) = c.enrolled_date
//            WHERE c.enrolled_date BETWEEN :start_date AND :end_date
//                AND FIND_IN_SET(c.cat_id,:customers)
//                AND u.active = 'Yes'
//                AND " . QueryHelper::NotExistsUnderBen('c.user_id') . "
//                AND u.id " . $this->excludeTestUsers() . "
//        ";
//
//        $customers = config('commission.member-types.customers');
//
//        $stmt = $this->db->prepare($sql);
//        $stmt->bindParam(":start_date", $start_date);
//        $stmt->bindParam(":end_date", $end_date);
//        $stmt->bindParam(":customers", $customers);
//        $stmt->execute();
//
//        return $stmt->fetchColumn();
    }

    public function getNewCustomersWithProductSubscription($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];


        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = $this->getNewCustomersWithProductSubscriptionQuery($start_date,$end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhere('t.transaction_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.fname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.lname', 'LIKE', "%{$search}%");

                if(stripos('customer', $search) !== false) {
                    $query->orWhere("t.sponsor_catid", 13);
                }
                if(stripos('affiliate', $search) !== false) {
                    $query->orWhereRaw("FIND_IN_SET(t.sponsor_catid,'14,15')");
                }
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getNewCustomersWithProductSubscriptionQuery($start_date, $end_date)
    {
        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');

        $query = DB::table('cm_customers AS c')
        ->selectRaw("c.user_id AS member_id,
                CONCAT(u.fname, ' ', u.lname) member,
                s.id AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                IFNULL(t.transaction_id, 'NO ORDERS YET') order_id,
                getCommissionValue(t.transaction_id) cv,
                IFNULL(t.amount, 0) amount_paid,
                -- IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                -- IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
                IF(t.transaction_id IS NULL, 
                    IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.sponsorid AND FIND_IN_SET(cm.catid, '$affiliates')), 'Affiliate', 'Customer'), 
                    IF(FIND_IN_SET(t.sponsor_catid, '$affiliates'), 'Affiliate', 'Customer')
                ) sponsor_type,
                tt.shipcity shipping_city,
                tt.shipstate shipping_state,
                'Y' has_subscription,
                COALESCE(u.cellphone, u.dayphone, 0) AS cellphone")
        ->join('users AS u', 'u.id', '=', 'c.user_id')
        ->join('users AS s', 's.id', '=', 'u.sponsorid')
        ->join('oc_autoship AS oa', function($join) {
            $join->on("oa.customer_id", "=", "c.user_id");
            $join->whereRaw("DATE(oa.purchasedate) = c.enrolled_date");
        })
        ->join("v_cm_transactions AS t", 't.user_id', '=', 'c.user_id')
        ->join('transactions AS tt', 'tt.id', '=', 't.transaction_id')
        ->whereRaw("c.enrolled_date BETWEEN '$start_date' AND '$end_date' AND oa.is_active = 1 AND u.active = 'Yes'
            AND FIND_IN_SET(c.cat_id, '$customers') AND u.id " . $this->excludeTestUsers())
        ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'));

        return $query;
    }

    public function getNewIBOCount($start_date, $end_date)
    {
        return $this->getNewIBOQuery($start_date,$end_date)->count();
//        $sql = "
//            SELECT
//                COUNT(1) endorser_count
//            FROM cm_affiliates a
//            JOIN users u ON u.id = a.user_id
//            JOIN users s ON s.id = u.sponsorid
//            WHERE a.affiliated_date BETWEEN :start_date AND :end_date
//                AND FIND_IN_SET(a.cat_id, :affiliates)
//                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "
//                AND a.user_id " . $this->excludeTestUsers() . "
//        ";
//
//        $affiliates = config('commission.member-types.ibo');
//
//        $stmt = $this->db->prepare($sql);
//        $stmt->bindParam(":start_date", $start_date);
//        $stmt->bindParam(":end_date", $end_date);
//        $stmt->bindParam(":affiliates", $affiliates);
//        $stmt->execute();
//
//        return $stmt->fetchColumn();
    }

    public function getNewIBO($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];


        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = $this->getNewIBOQuery($start_date,$end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhere('vct.transaction_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.fname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.lname', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(u.fname, ' ', u.lname) LIKE ?", ['%'.$search.'%'])
                    ->orWhereRaw("CONCAT(ul.fname, ' ', ul.lname) LIKE ?", ['%'.$search.'%']);

                if(stripos('customer', $search) !== false) {
                    $query->orWhere("vct.sponsor_catid", 13);
                }
                if(stripos('affiliate', $search) !== false) {
                    $query->orWhereRaw("FIND_IN_SET(vct.sponsor_catid,'14,15')");
                }
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getNewIBOQuery($start_date, $end_date)
    {
        $ibo = config('commission.member-types.ibo');
        $affiliates = config('commission.member-types.affiliates');

        $query = DB::table('cm_affiliates AS cc')
            ->selectRaw("cc.user_id AS member_id, 
                  CONCAT(u.fname,' ', u.lname) AS member, 
                  ul.id sponsor_id, 
                  CONCAT(ul.fname,' ', ul.lname) AS sponsor,
                  IF(FIND_IN_SET(vct.sponsor_catid, '$affiliates'), 'Affiliate', 'Customer') AS sponsor_type,
                  IFNULL(vct.transaction_id, 'NO ORDERS YET') order_id,
                  getCommissionValue(vct.transaction_id) AS cv,
                  IFNULL(vct.amount, 0) AS amount_paid,
                  IF(oa.is_active = 1,'Y','N') has_subscription, 
                  IFNULL(u.cellphone, u.dayphone) AS cellphone")
            ->join('users AS u', 'u.id', '=', 'cc.user_id')
            ->join('users AS ul', 'ul.id', '=', 'u.sponsorid')
            ->leftJoin('oc_autoship AS oa', 'oa.customer_id', '=', 'u.id')
            ->leftJoin("v_cm_transactions AS vct", function($join) {
                $join->on('vct.user_id', '=', 'cc.user_id');
                $join->whereRaw('vct.transaction_id = (
                        SELECT
                        vct1.transaction_id
                      FROM v_cm_transactions vct1
                      WHERE vct1.user_id = cc.user_id
                      ORDER BY vct1.transaction_id ASC LIMIT 1)');
            })
            ->whereRaw("cc.affiliated_date BETWEEN ? AND ? ", [$start_date, $end_date])
            ->whereRaw("vct.purchaser_catid = $ibo AND u.active = 'Yes'")
            ->whereRaw("u.id ".$this->excludeTestUsers())
            ->whereRaw(QueryHelper::NotExistsUnderBen('cc.user_id'));

        return $query;

    }

    public function getNewIBOWithProductSubscriptionCount($start_date, $end_date)
    {
        return $this->getNewIBOWithProductSubscriptionQuery($start_date,$end_date)->count();
//        $sql = "
//            SELECT
//                COUNT(1) endorser_count
//            FROM cm_affiliates a
//            JOIN users u ON u.id = a.user_id
//            JOIN users s ON s.id = u.sponsorid
//            JOIN oc_autoship oa ON oa.customer_id = u.id AND oa.is_active = 1 AND DATE(oa.purchasedate) = a.affiliated_date
//            WHERE a.affiliated_date BETWEEN :start_date AND :end_date
//                AND FIND_IN_SET(a.cat_id, :affiliates)
//                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "
//                AND a.user_id " . $this->excludeTestUsers() . "
//        ";
//
//        $affiliates = config('commission.member-types.ibo');
//
//        $stmt = $this->db->prepare($sql);
//        $stmt->bindParam(":start_date", $start_date);
//        $stmt->bindParam(":end_date", $end_date);
//        $stmt->bindParam(":affiliates", $affiliates);
//        $stmt->execute();
//
//        return $stmt->fetchColumn();
    }

    public function getNewIBOWithProductSubscription($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];


        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = $this->getNewIBOWithProductSubscriptionQuery($start_date,$end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhere('vct.transaction_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.fname', 'LIKE', "%{$search}%")
                    ->orWhere('ul.lname', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(u.fname, ' ', u.lname) LIKE ?", ['%'.$search.'%'])
                    ->orWhereRaw("CONCAT(ul.fname, ' ', ul.lname) LIKE ?", ['%'.$search.'%']);

                if(stripos('customer', $search) !== false) {
                    $query->orWhere("vct.sponsor_catid", 13);
                }
                if(stripos('affiliate', $search) !== false) {
                    $query->orWhereRaw("FIND_IN_SET(vct.sponsor_catid,'14,15')");
                }
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getNewIBOWithProductSubscriptionQuery($start_date, $end_date)
    {
        $ibo_with_autoship = config('commission.member-types.ibo-autoship');
        $affiliates = config('commission.member-types.affiliates');

        $query = DB::table('cm_affiliates AS cc')
            ->selectRaw("cc.user_id AS member_id, 
                  CONCAT(u.fname,' ', u.lname) AS member, 
                  ul.id AS sponsor_id, 
                  CONCAT(ul.fname,' ', ul.lname) AS sponsor,
                  IF(FIND_IN_SET(vct.sponsor_catid, '$affiliates'), 'Affiliate', 'Customer') AS sponsor_type,
                  IFNULL(vct.transaction_id, 'NO ORDERS YET') AS order_id,
                  getCommissionValue(vct.transaction_id) AS cv,
                  IFNULL(vct.amount, 0) AS amount_paid,
                  IF(oa.is_active = 1,'Y','N') has_subscription, 
                  IFNULL(u.cellphone, u.dayphone) AS cellphone")
            ->join('users AS u', 'u.id', '=', 'cc.user_id')
            ->join('users AS ul', 'ul.id', '=', 'u.sponsorid')
            ->leftJoin('oc_autoship AS oa', 'oa.customer_id', '=', 'u.id')
            ->leftJoin("v_cm_transactions AS vct", function($join) {
                $join->on('vct.user_id', '=', 'cc.user_id');
                $join->whereRaw('vct.transaction_id = (
                        SELECT
                        vct1.transaction_id
                      FROM v_cm_transactions vct1
                      WHERE vct1.user_id = cc.user_id
                      ORDER BY vct1.transaction_id ASC LIMIT 1)');
            })
            ->whereRaw("cc.affiliated_date BETWEEN ? AND ? ", [$start_date, $end_date])
            ->whereRaw("vct.purchaser_catid = $ibo_with_autoship AND u.active = 'Yes'")
            ->whereRaw("u.id ".$this->excludeTestUsers())
            ->whereRaw(QueryHelper::NotExistsUnderBen('cc.user_id'));

        return $query;
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
                                ORDER BY tt.transaction_date ASC
                                LIMIT 1
                        )
                        AND t.type = 'product'
                        AND " . QueryHelper::NotExistsUnderBen('t.user_id') . "
                        AND t.user_id " . $this->excludeTestUsers() . "
                ) /
                (
                
                    SELECT 
                        COUNT(DISTINCT t.user_id)
                    FROM v_cm_transactions t
                    WHERE t.transaction_date BETWEEN :start_date1 AND :end_date1
                        AND " . QueryHelper::NotExistsUnderBen('t.user_id') . "
                        AND t.user_id " . $this->excludeTestUsers() . "
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
                AND u.id " . $this->excludeTestUsers() . "
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
    //GET SUM OF PLATINUM AND GOLD PACKAGE
    private function getPackTotalSales($pack_ids, $purchaser_catid, $start_date, $end_date)
    {
        return DB::table('v_cm_transactions AS vct')
                ->join('users AS u', 'u.id', '=', 'vct.user_id')
                ->where('vct.purchaser_catid', $purchaser_catid)
                ->whereRaw("vct.transaction_date BETWEEN ? AND ? ", [$start_date, $end_date])
                ->whereRaw("EXISTS(SELECT 1 FROM transaction_products tp WHERE FIND_IN_SET(tp.shoppingcart_product_id,'$pack_ids')  AND tp.transaction_id = vct.transaction_id)")
                ->whereRaw("u.id ".$this->excludeTestUsers())
                ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'))->sum("vct.amount");

    }
    //QUERY TO GET EACH PURCHASE
    private function getPackSales($pack_ids, $purchaser_catid, $start_date, $end_date)
    {
        $affiliates = config('commission.member-types.affiliates');
        $query = DB::table('v_cm_transactions AS vct')
            ->selectRaw("vct.user_id AS purchaser_id,
              CONCAT(u.fname,' ', u.lname) AS purchaser, 
              vct.invoice,
              ul.id sponsor_id, 
              CONCAT(ul.fname,' ', ul.lname) AS sponsor,
              IFNULL(vct.transaction_id, 'NO ORDERS YET') AS order_id,
              getCommissionValue(vct.transaction_id) AS cv,
              IFNULL(vct.amount, 0) AS amount_paid,
              IF(FIND_IN_SET(vct.sponsor_catid, '$affiliates'), 'Affiliate', 'Customer') AS sponsor_type,
              IF((SELECT
                  COUNT(*)
              FROM cm_clawbacks cc
              WHERE cc.transaction_id = vct.transaction_id),'Yes','No') AS is_clawback")
            ->join('users AS u', 'u.id', '=', 'vct.user_id')
            ->join('users AS ul', 'ul.id', '=', 'u.sponsorid')
            ->where('vct.purchaser_catid', $purchaser_catid)
            ->whereRaw("vct.transaction_date BETWEEN ? AND ? ", [$start_date, $end_date])
            ->whereRaw("EXISTS(SELECT 1 FROM transaction_products tp WHERE FIND_IN_SET(tp.shoppingcart_product_id,'$pack_ids') AND tp.transaction_id = vct.transaction_id)")
            ->whereRaw("u.id ".$this->excludeTestUsers())
            ->whereRaw(QueryHelper::NotExistsUnderBen('vct.user_id'));

        return $query;
    }

    public function getPlatinumPackageTotalSales($start_date, $end_date)
    {
        $pack_ids = config('commission.products.platinum_package');
        $ibo_with_autoship = config('commission.member-types.ibo-autoship');
        return $this->getPackTotalSales($pack_ids, $ibo_with_autoship, $start_date, $end_date);
    }

    public function getPlatinumPackageSales($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];


        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }
        $pack_ids = config('commission.products.platinum_package');
        $ibo_with_autoship = config('commission.member-types.ibo-autoship');

        $query = $this->getPackSales($pack_ids, $ibo_with_autoship, $start_date, $end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhere('tt.amount', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('tt.invoice', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getGoldPackageTotalSales($start_date, $end_date)
    {
        $pack_ids = config('commission.products.gold_package');
        $customers = config('commission.member-types.customers');
        return $this->getPackTotalSales($pack_ids, $customers, $start_date, $end_date);
    }

    public function getGoldPackageSales($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];


        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $pack_ids = config('commission.products.gold_package');
        $customers = config('commission.member-types.customers');
        $query = $this->getPackSales($pack_ids, $customers, $start_date, $end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhere('tt.amount', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('tt.invoice', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getIBOQuery($start_date,$end_date)
    {
        $ibo = config('commission.member-types.ibo');
        $query = DB::table('v_cm_transactions AS vct')
            ->join('users AS u', 'u.id', '=', 'vct.user_id')
            ->where('vct.purchaser_catid', $ibo)
            ->whereRaw("vct.transaction_date BETWEEN ? AND ? ", [$start_date, $end_date])
            ->whereRaw("EXISTS(SELECT vct1.transaction_id FROM v_cm_transactions vct1 WHERE vct1.transaction_id = vct.transaction_id ORDER BY vct1.transaction_id ASC LIMIT 1)") //first order
            ->whereRaw("u.id ".$this->excludeTestUsers())
            ->whereRaw(QueryHelper::NotExistsUnderBen('u.id'))->sum("vct.amount");
        return $query;
    }

    public function getIBOTotalSales($start_date, $end_date)
    {
        return $this->getIBOQuery($start_date,$end_date);
    }

    public function getIBOPurchasers($start_date, $end_date)
    {
        $ibo = config('commission.member-types.ibo');
        $affiliates  = config('commission.member-types.affiliates');
        $query = DB::table('v_cm_transactions AS vct')
            ->selectRaw("vct.user_id AS purchaser_id,
              CONCAT(u.fname,' ', u.lname) AS purchaser, 
              vct.invoice,
              ul.id sponsor_id, 
              CONCAT(ul.fname,' ', ul.lname) AS sponsor,
              IFNULL(vct.transaction_id, 'NO ORDERS YET') AS order_id,
              getCommissionValue(vct.transaction_id) AS cv,
              IFNULL(vct.amount, 0) AS amount_paid,
              IF(FIND_IN_SET(vct.sponsor_catid, '$affiliates'), 'Affiliate', 'Customer') AS sponsor_type,
              IF((SELECT
                  COUNT(*)
              FROM cm_clawbacks cc
              WHERE cc.transaction_id = vct.transaction_id),'Yes','No') AS is_clawback")
            ->join('users AS u', 'u.id', '=', 'vct.user_id')
            ->join('users AS ul', 'ul.id', '=', 'u.sponsorid')
            ->where('vct.purchaser_catid', $ibo)
            ->whereRaw("vct.transaction_date BETWEEN ? AND ? ", [$start_date, $end_date])
            ->whereRaw("EXISTS(SELECT vct1.transaction_id FROM v_cm_transactions vct1 WHERE vct1.transaction_id = vct.transaction_id ORDER BY vct1.transaction_id ASC LIMIT 1)") //first order
            ->whereRaw("u.id ".$this->excludeTestUsers())
            ->whereRaw(QueryHelper::NotExistsUnderBen('vct.user_id'));
        return $query;
    }

    public function getIBOSales($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];


        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = $this->getIBOPurchasers($start_date, $end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhere('tt.amount', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('tt.invoice', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getTopEndorsers($start_date, $end_date)
    {
        $sql = "
            SELECT
                a.user_id,
                CONCAT(u.fname, ' ', u.lname) endorser,
                DENSE_RANK() OVER(ORDER BY c.ibo_count DESC) AS ranking,
                IFNULL(c.ibo_count, 0) AS ibo_count,
                IFNULL(v.volume, 0) AS volume
            FROM cm_affiliates a
            JOIN users u ON u.id = a.user_id
            LEFT JOIN (
                SELECT 
                    COUNT(1) ibo_count,
                    u.sponsorid AS user_id
                FROM cm_affiliates aa
                JOIN users u ON u.id = aa.user_id
                JOIN users s ON s.id = u.sponsorid
                WHERE aa.affiliated_date BETWEEN :start_date AND :end_date
                    AND FIND_IN_SET(aa.cat_id, :affiliates)
                    AND " . QueryHelper::NotExistsUnderBen('aa.user_id') . "
                    AND aa.user_id " . $this->excludeTestUsers() . "
                GROUP BY u.sponsorid
            ) c ON c.user_id = a.user_id
            LEFT JOIN (
                SELECT 
                     IFNULL((
                    SELECT SUM(getCommissionValue(t.transaction_id))
                    FROM v_cm_transactions t 
                    WHERE t.user_id = aa.user_id
                        AND DATE(t.transaction_date) BETWEEN :start_date1 AND :end_date1
                    ),0) volume,
                    u.sponsorid AS user_id
                FROM cm_affiliates aa
                JOIN users u ON u.id = aa.user_id
                JOIN users s ON s.id = u.sponsorid
                WHERE aa.affiliated_date BETWEEN :start_date2 AND :end_date2
                    AND FIND_IN_SET(aa.cat_id, :affiliates1)
                    AND " . QueryHelper::NotExistsUnderBen('aa.user_id') . "
                    AND aa.user_id " . $this->excludeTestUsers() . "
                GROUP BY u.sponsorid
            ) v ON v.user_id = a.user_id
            WHERE EXISTS( SELECT 1 FROM categorymap c WHERE c.userid = a.user_id AND FIND_IN_SET(c.catid, :affiliates2))
                AND " . QueryHelper::NotExistsUnderBen('a.user_id') . "
                AND a.user_id " . $this->excludeTestUsers() . "
            -- GROUP BY a.user_id
            HAVING ibo_count > 0
            ORDER BY ibo_count DESC, volume DESC
        ";

        $affiliates = config('commission.member-types.affiliates');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":start_date1", $start_date);
        $stmt->bindParam(":start_date2", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":end_date1", $end_date);
        $stmt->bindParam(":end_date2", $end_date);
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
                -- IF(EXISTS(SELECT 1 FROM oc_coupon_history och WHERE och.order_id = t.id), 'Y', 'N') has_coupon,
                -- IF(EXISTS(SELECT 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id), 'Y', 'N') has_gift_card,
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
                  AND aa.user_id " . $this->excludeTestUsers() . "
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
    const REPORT_NEW_IBO = "NEW_IBO";
    const REPORT_NEW_IBO_PS = "NEW_IBO_PS";
    const REPORT_PLATINUM_PACKAGE = "PLATINUM_PACKAGE";
    const REPORT_GOLD_PACKAGE = "GOLD_PACKAGE";
    const REPORT_IBO_SALES = "IBO_SALES";
    const REPORT_TOP_IBO = "TOP_IBO";
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
                $data = $this->getNewCustomersQuery($start_date, $end_date)->get();
                break;
            case static::REPORT_NEW_CUSTOMERS_PS:
                $data = $this->getNewCustomersWithProductSubscriptionQuery($start_date, $end_date)->get();
                break;
            case static::REPORT_NEW_IBO:
                $data = $this->getNewIBOQuery($start_date, $end_date)->get();
                break;
            case static::REPORT_NEW_IBO_PS:
                $data = $this->getNewIBOWithProductSubscriptionQuery($start_date, $end_date)->get();
                break;
            case static::REPORT_PLATINUM_PACKAGE:
                $data = $this->getPackSales(config('commission.products.platinum_package'),config('commission.member-types.ibo-autoship'),$start_date, $end_date)->get();
                break;
            case static::REPORT_GOLD_PACKAGE:
                $data = $this->getPackSales(config('commission.products.gold_package'),config('commission.member-types.customers'),$start_date, $end_date)->get();
                break;
            case static::REPORT_IBO_SALES:
                $data = $this->getIBOQuery($start_date, $end_date)->get();
                break;
            case static::REPORT_TOP_IBO:
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

    public function excludeTestUsers()
    {
        return "
            NOT IN (19825, 19936, 20398, 19918, 19921, 10)
        ";
    }

}