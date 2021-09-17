<?php

namespace Commissions\Member;

use PDO;
use Carbon\Carbon;
use App\CommissionPeriod;
use Illuminate\Support\Facades\DB;

class PartyManager
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->customers = config('commission.member-types.customers');
    }

    public function create($filters, $sponsor_id) 
    {
        $hostess_id = isset($filters['hostess_id']) ? $filters['hostess_id'] : null;
        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date = isset($filters['end_date']) ? $filters['end_date'] : null;
        $period = isset($filters['period']) ? $filters['period'] : null;

        $promo_period = str_replace('/', '-', explode('-', $period));
        $start_date = Carbon::createFromFormat('m-d-Y', trim($promo_period[0]))->toDateString();
        $end_date = Carbon::createFromFormat('m-d-Y', trim($promo_period[1]))->toDateString();

        if (empty($hostess_id)) throw new \Exception("Select a Hostess.");

        if ($this->isExists($hostess_id, $start_date, $sponsor_id)) throw new \Exception("Event Date already exists.");

        return DB::transaction(function() use ($hostess_id, $start_date, $end_date, $sponsor_id) {

            $sitename = DB::table('users')->select(DB::raw("TRIM(site) AS site"))->where('id', $hostess_id)->first();
            $sharing_link = $this->getSharingLink($sitename->site);
            $sharing_link = str_replace(' ', '', $sharing_link);
            $tinyUrl = $this->getTinyUrl($sharing_link);

            $hostess_data = [
                'user_id' => $hostess_id,
                'sitename' => $sitename->site,
                'sponsor_id' => $sponsor_id,
                'social_link' => $sharing_link,
                'social_link_shorten' => $tinyUrl,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];

            return DB::table('cm_hostess_program')->insert($hostess_data);
        });
    }

    public function delete($id) 
    {
        return DB::transaction(function() use ($id) {
            return DB::table('cm_hostess_program')->where('id', $id)->update(['is_deleted' => 1]);
        });
    }

    public function getOpenEvents($filters, $member_id)
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
            ->select(
                DB::raw("CONCAT(u.id, ': ', u.fname, ' ', u.lname) AS hostess"),
                'hp.social_link AS sharing_link',
                'hp.start_date AS start_date',
                'hp.end_date AS end_date',
                DB::raw("IF(DATEDIFF(hp.end_date, CURRENT_DATE()) < 0, 0, DATEDIFF(hp.end_date, CURRENT_DATE())) AS time_left"),
                DB::raw("IFNULL(ROUND(SUM(t.`computed_cv`), 2), 0) AS total_sales"),
                'hp.id AS hostess_program_id',
                DB::raw("IF(CURRENT_DATE() < hp.start_date, 1, 0) AS is_active")
            )
            ->join('users AS u', 'hp.user_id', '=', 'u.id')
            ->leftjoin('v_cm_transactions AS t', function($join) {
                $join->whereRaw("(t.user_id = hp.user_id OR t.sponsor_id = hp.user_id)")
                    ->whereRaw("t.transaction_date BETWEEN hp.start_date AND hp.end_date");
            })
            ->where('hp.sponsor_id', $member_id)
            ->where('hp.is_deleted', '0')
            ->whereRaw("CURRENT_DATE() < hp.end_date")
            ->groupBy('hp.id');


        $recordsTotal = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('u.hostess_id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('hp.start_date', 'LIKE', "%{$search}%")
                    ->orWhere('hp.end_date', $search)
                    ->orWhere('hp.social_link', $search);
            });
        }

        $recordsFiltered = $recordsTotal;

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('hp.end_date', 'asc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }
        
        $q = $query->toSql();
        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'q');
    }

    public function getPastEvents($filters, $member_id)
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
            ->select(
                DB::raw("CONCAT(u.id, ': ', u.fname, ' ', u.lname) AS hostess"),
                'hp.social_link AS sharing_link',
                'hp.start_date AS start_date',
                'hp.end_date AS end_date',
                DB::raw("IFNULL(ROUND(SUM(t.`computed_cv`), 2), 0) AS total_sales"),
                'hp.id AS hostess_program_id'
            )
            ->join('users AS u', 'hp.user_id', '=', 'u.id')
            ->leftjoin('v_cm_transactions AS t', function($join) {
                $join->whereRaw("(t.user_id = hp.user_id OR t.sponsor_id = hp.user_id)")
                    ->whereRaw("t.transaction_date BETWEEN hp.start_date AND hp.end_date");
            })
            ->where('hp.sponsor_id', $member_id)
            ->where('hp.is_deleted', '0')
            ->whereRaw("CURRENT_DATE() > hp.end_date")
            ->groupBy('hp.id');


        // $recordsTotal = $query->count(DB::raw("1"));
        $recordsTotal = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('hp.start_date', 'LIKE', "%{$search}%")
                    ->orWhere('hp.end_date', $search)
                    ->orWhere('hp.social_link', $search);
            });
        }

        // $recordsFiltered = $query->count(DB::raw("1"));
        $recordsFiltered = $recordsTotal;

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('hp.end_date', 'asc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }
        
        $q = $query->toSql();
        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'q');
    }

    public function getTopHostesses($filters, $member_id)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date = isset($filters['end_date']) ? $filters['end_date'] : null;

        if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date', 'end_date', 'user_id');
        }

        $query = DB::table('v_cm_transactions AS t')
            ->select(
                DB::raw("CONCAT(u.id, ': ', u.fname, ' ', u.lname) AS hostess"),
                DB::raw("CONCAT('https://www.opulenzadesigns.com/', u.site) AS sharing_link"),
                DB::raw("IFNULL(ROUND(SUM(t.`computed_cv`), 2), 0) AS total_sales")
            )
            ->join('users AS u', 'u.id', '=', 't.user_id')
            ->where('u.sponsorid', $member_id)
            ->whereRaw("(t.user_id = u.id OR t.sponsor_id = u.id)")
            ->whereRaw("FIND_IN_SET(t.`purchaser_catid`, '$this->customers')")
            ->whereRaw("t.transaction_date BETWEEN '$start_date' AND '$end_date'")
            ->groupBy('u.id')
            ->having('total_sales', '>', 0);


        // $recordsTotal = $query->count(DB::raw("1"));
        $recordsTotal = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search) && false) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%");
            });
        }

        // $recordsFiltered = $query->count(DB::raw("1"));
        // count total filtered records
        $recordsFiltered = $recordsTotal;

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $queryDump = $query->toSql();
        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date', 'end_date', 'queryDump');
    }

    public function getOrders($id)
    {
        $sql = "
            SELECT 
                CONCAT(u.fname, ' ', u.lname) AS customer, t.transaction_id AS order_id, p.sku AS description, t.`computed_cv` AS amount
            FROM cm_hostess_program hp 
            JOIN v_cm_transactions t ON (t.`user_id` = hp.`user_id` OR t.sponsor_id = hp.`user_id`) AND t.`transaction_date` BETWEEN hp.`start_date` AND hp.`end_date`
            JOIN users u ON u.id = t.`user_id`
            LEFT JOIN oc_product p ON p.`product_id` = t.`item_id`
            WHERE FIND_IN_SET(t.`purchaser_catid`, :customers)
                AND hp.id = :id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":customers", $this->customers);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLogs($member_id)
    {
        $sql = "
                SELECT 
                    hp.`social_link_shorten` AS social_link, CONCAT(u.fname, ' ', u.lname) AS hostess_name, hp.`start_date`, hp.`end_date`, hp.`updated_at` AS update_date
                FROM cm_hostess_program hp
                JOIN users u ON hp.`user_id` = u.id
                WHERE is_deleted = 1 
                    AND hp.`sponsor_id` = :member_id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":member_id", $member_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getSharingLink($site)
    {
        return "https://www.opulenzadesigns.com/" . $site;
    }

    public function crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);
        return $min + $rnd;
    }

    public function getToken($length)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); // edited

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max-1)];
        }

        return strtoupper($token);
    }

    function getTinyUrl($url)
    {
        $ch = curl_init(); 
        $timeout = 5; 
        curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url='.$url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
        $data = curl_exec($ch); 
        curl_close($ch); 

        return ''.$data.''; 
    }

    public function isExists($hostess_id, $start_date, $sponsor_id) 
    {
        $sql = "
            SELECT *
            FROM cm_hostess_program 
            WHERE user_id = :user_id AND sponsor_id = :sponsor_id 
                AND :start_date BETWEEN start_date AND end_date 
                AND is_deleted = 0
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $hostess_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":sponsor_id", $sponsor_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return !empty($result) ? true : false;
    }

}