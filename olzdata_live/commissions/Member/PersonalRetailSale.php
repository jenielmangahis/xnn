<?php


namespace Commissions\Member;


use App\DailyVolume;
use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;

class PersonalRetailSale
{
    const REPORT_PATH = "csv/member/personal_retail";

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getEnrollment($filters, $user_id = null)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search  = $filters['search'];
        $order   = $filters['order'];
        $columns = $filters['columns'];

        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date   = isset($filters['end_date']) ? $filters['end_date'] : null;
        $memberId   = isset($filters['memberId']) ? $filters['memberId'] : null;
        $prs_500_above = $filters['prs_500_above'] == 'true' ? $filters['prs_500_above'] : null;        
        $transaction_start_date = isset($filters['transaction_start_date']) ? $filters['transaction_start_date'] : null;
        $transaction_end_date   = isset($filters['transaction_end_date']) ? $filters['transaction_end_date'] : null;

        /*if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date');
        }*/

        $level = 0;

        $query = $this->getEnrollmentQuery($user_id, $start_date, $end_date, $prs_500_above, $level, $memberId, $transaction_start_date, $transaction_end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search, $level) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhereRaw("dv.level - $level = ?", [$search]);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            if( $column['column'] == 9 ){
                $query->orderByRaw('(COALESCE(a.ps, 0) + COALESCE(c.cs, 0)) ' . $column['dir']);
            }else{
                $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);    
            }
            
        }else{
            $query->orderByRaw('(COALESCE(a.ps, 0) + COALESCE(c.cs, 0)) desc');
        }

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id', 'start_date');
    }

    protected function getEnrollmentQuery($user_id, $start_date, $end_date, $prs_500_above, &$level = 0, $memberId, $transaction_start_date, $transaction_end_date)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers  = config('commission.member-types.customers');

        DB::statement(DB::raw('SET @rownum=0'));

        $level = 0;

        if ($end_date > date('Y-m-d')) {
            $end_date = date('Y-m-d');
        }

        if ($start_date > date('Y-m-d')) {
            $start_date = date('Y-m-d');
        }

        if (!!$user_id) {

            $volume = DailyVolume::ofMember($user_id)->date($end_date)->first();
            $level = $volume === null ? 0 : +$volume->level;
        }

        $query =
            DB::table('cm_daily_volumes AS dv')
            ->leftJoin(
                DB::raw("
                (
                    SELECT
                        t.user_id,
                        SUM(COALESCE(t.computed_cv, 0)) As ps
                    FROM v_cm_transactions t
                    WHERE transaction_date BETWEEN '$transaction_start_date' AND '$transaction_end_date'
                        AND t.`type` = 'product'
                        AND FIND_IN_SET(t.purchaser_catid, '$affiliates')
                    GROUP BY t.user_id
                ) AS a"),"a.user_id", "=", "dv.user_id"
            )
            ->leftJoin(
                DB::raw("
                (
                    SELECT
                        ti.upline_id AS user_id,
                        SUM(COALESCE(t.computed_cv, 0)) AS cs
                    FROM v_cm_transactions t
                    JOIN cm_transaction_info ti ON ti.transaction_id = t.transaction_id
                    WHERE t.transaction_date BETWEEN '$transaction_start_date' AND '$transaction_end_date'
                        AND t.`type` = 'product' 
                        AND FIND_IN_SET(t.purchaser_catid, '$customers')
                    GROUP BY ti.upline_id
                ) AS c"),"c.user_id", "=", "dv.user_id"
            )
            ->join("cm_daily_ranks AS dr", "dr.volume_id", "=", "dv.id")
            ->join("users AS u", "u.id", "=", "dr.user_id")
            ->join("cm_ranks AS cr", "cr.id", "=", "dr.rank_id")
            ->join("cm_ranks AS pr", "pr.id", "=", "dr.paid_as_rank_id")
            ->join("cm_affiliates AS ca", "u.id", "=", "ca.user_id")
            ->leftJoin("users AS s", "s.id", "=", "u.sponsorid")
            ->selectRaw("
                @rownum  := @rownum  + 1 AS top,
                dv.user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.enrolled_date,
                ca.affiliated_date,
                u.email,
                u.country,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                COALESCE(a.ps, 0) + COALESCE(c.cs, 0) AS total_prs
            ")            
        ;

        $query->groupBy(['dv.user_id']);

        if( !!$start_date && !!$end_date ){
            $query->whereBetween('u.enrolled_date', [$start_date, $end_date]);
        }

        if( $prs_500_above ){
            $query->whereRaw('(COALESCE(a.ps, 0) + COALESCE(c.cs, 0)) > 500');
        } 

        if (!!$user_id) {
            $query->whereRaw("EXISTS(
                WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`
                    FROM users
                    WHERE sponsorid = ? AND levelid = 3
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE p.levelid = 3
                )
                SELECT 1 FROM downline d WHERE d.user_id = dv.user_id
            )", [$user_id]);
        }

        //Filter by member id
        if (!!$memberId) {
            $query = $query->where('u.id', $memberId);
        }

        return $query;
    }

    public function getPersonalRetailDownloadLink($filters, $user_id = null)
    {
        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date   = isset($filters['end_date']) ? $filters['end_date'] : null;
        $memberId   = isset($filters['memberId']) ? $filters['memberId'] : null;
        $prs_500_above = $filters['prs_500_above'] == 'true' ? $filters['prs_500_above'] : null;        
        $transaction_start_date = isset($filters['transaction_start_date']) ? $filters['transaction_start_date'] : null;
        $transaction_end_date   = isset($filters['transaction_end_date']) ? $filters['transaction_end_date'] : null;

        $level = 0;
        $data  = $this->getEnrollmentQuery($user_id, $start_date, $end_date, $prs_500_above, $level, $memberId, $transaction_start_date, $transaction_end_date)->get();        
        $csv      = new CsvReport(static::REPORT_PATH);
        $filename = "personal-retail-$transaction_start_date-$transaction_end_date-";

        if ($memberId !== null) {
            $filename .= "$memberId-";
        }

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }
}