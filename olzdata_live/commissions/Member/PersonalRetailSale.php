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
        $volume_start_date = isset($filters['volume_start_date']) ? $filters['volume_start_date'] : null;
        $volume_end_date   = isset($filters['volume_end_date']) ? $filters['volume_end_date'] : null;

        if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date');
        }

        $level = 0;

        $query = $this->getEnrollmentQuery($user_id, $start_date, $end_date, $prs_500_above, $level, $memberId, $volume_start_date, $volume_end_date);

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
                $query->where('pr.name', 'LIKE', "%{$search}%")
                    ->orWhere('cr.name', 'LIKE', "%{$search}%")
                    ->orWhere('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }else{
            $query->orderBy("dv.prs", "DESC");
        }

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id', 'start_date');
    }

    protected function getEnrollmentQuery($user_id, $start_date, $end_date, $prs_500_above, &$level = 0, $memberId, $volume_start_date, $volume_end_date)
    {
        DB::statement(DB::raw('set @rownum=0'));

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
            ->join("cm_daily_ranks AS dr", "dr.volume_id", "=", "dv.id")
            ->join("users AS u", "u.id", "=", "dr.user_id")
            ->join("cm_ranks AS cr", "cr.id", "=", "dr.rank_id")
            ->join("cm_ranks AS pr", "pr.id", "=", "dr.paid_as_rank_id")
            ->join("cm_affiliates AS ca", "u.id", "=", "ca.user_id")
            ->leftJoin("users AS s", "s.id", "=", "u.sponsorid")
            ->selectRaw("
                @rownum  := @rownum  + 1 AS rownum,
                dv.user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.email,
                u.country,
                u.enrolled_date,
                ca.affiliated_date,
                dr.rank_id,
                cr.name AS current_rank,
                dr.paid_as_rank_id,
                pr.name AS paid_as_rank,
                dv.prs,
                dv.grs,
                dv.sponsored_qualified_representatives_count,
                dv.sponsored_leader_or_higher_count,
                dr.is_active,
                dv.level - $level AS level,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                dr.rank_date
            ");

        if( !!$start_date && !!$end_date ){
            echo 4444;
            $query->whereBetween('u.enrolled_date', [$start_date, $end_date]);
        }

        if( !!$volume_start_date && !!$volume_end_date ){
            $query->whereBetween('dv.volume_date', [$volume_start_date, $volume_end_date]);
        }

        if( $prs_500_above ){
            $query->where('dv.prs', '>=', 500);
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
        $level = 0;
        $start_date    = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date      = isset($filters['end_date']) ? $filters['end_date'] : null;
        $prs_500_above = $filters['prs_500_above'] == 'true' ? $filters['prs_500_above'] : null;
        $memberId      = isset($filters['memberId']) ? $filters['memberId'] : null;
        $volume_start_date = isset($filters['volume_start_date']) ? $filters['volume_start_date'] : null;
        $volume_end_date   = isset($filters['volume_end_date']) ? $filters['volume_end_date'] : null;

        $csv   = new CsvReport(static::REPORT_PATH);

        if (!$start_date || !$end_date) {
            $data = [];
        } else {
            $data = $this->getEnrollmentQuery($user_id, $start_date, $end_date, $prs_500_above, $level, $memberId, $volume_start_date, $volume_end_date)->get();
        }

        $filename = "personal-retail-$start_date-$end_date-";

        if ($memberId !== null) {
            $filename .= "$memberId-";
        }

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }
}