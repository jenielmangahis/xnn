<?php


namespace Commissions\Member;


use App\DailyVolume;
use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;

class RankHistory
{
    const REPORT_PATH = "csv/member/rank_history";

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

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $rank_id = isset($filters['rank_id']) ? $filters['rank_id'] : "";

        if (!$start_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date');
        }

        $level = 0;

        $query = $this->getEnrollmentQuery($user_id, $start_date, $rank_id, $level);

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
        }

        $query->orderBy("dv.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id', 'start_date');
    }

    public function getPersonal($filters, $user_id = null)
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
        $rank_id = isset($filters['rank_id']) ? $filters['rank_id'] : "";

        if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date', 'end_date', 'user_id');
        }

        $level = 0;
        if (!!$user_id) {

            $volume = DailyVolume::ofMember($user_id)->date($start_date)->first();

            $level = $volume === null ? 0 : +$volume->level;
        }

        $query = DB::table('cm_daily_volumes AS dv')
            ->join("cm_daily_ranks AS dr", "dr.volume_id", "=", "dv.id")
            ->join("users AS u", "u.id", "=", "dr.user_id")
            ->join("cm_ranks AS cr", "cr.id", "=", "dr.rank_id")
            ->join("cm_ranks AS pr", "pr.id", "=", "dr.paid_as_rank_id")
            ->selectRaw("
                dv.user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                dr.rank_id,
                cr.name AS current_rank,
                dr.paid_as_rank_id,
                pr.name AS paid_as_rank,
                dv.prs,
                dv.grs,
                dv.sponsored_qualified_representatives_count,
                dv.level - $level AS level,
                dr.is_active,
                dr.rank_date
            ")
            ->where('dv.user_id', $user_id)
            ->whereBetween('dv.volume_date', [$start_date, $end_date]);

        if (!!$rank_id) {
            $query->where('dr.paid_as_rank_id', $rank_id);
        }

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search) && false) {

            $query->where(function ($query) use ($search) {
                $query->where('dv.user_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('pr.name', 'LIKE', "%{$search}%")
                    ->orWhere('cr.name', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("dv.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date', 'end_date', 'user_id');
    }

    public function getHighest($filters, $user_id = null)
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
        $is_all = isset($filters['is_all']) ? +$filters['is_all'] : 0;
        $rank_id = isset($filters['rank_id']) ? +$filters['rank_id'] : "";

        if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date');
        }

        $query = $this->getHighestQuery($user_id, $start_date, $end_date, $rank_id, $is_all);

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
                $query->where('r.name', 'LIKE', "%{$search}%")
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
        }

        $query->orderBy("a.user_id", "ASC")->orderBy("a.rank_id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'user_id', 'start_date', 'end_date', 'is_all', 'rank_id');
    }

    protected function getEnrollmentQuery($user_id, $start_date, $rank_id, &$level = 0)
    {
        $level = 0;

        if (!!$user_id) {

            $volume = DailyVolume::ofMember($user_id)->date($start_date)->first();

            $level = $volume === null ? 0 : +$volume->level;
        }

        $query =
            DB::table('cm_daily_volumes AS dv')
            ->join("cm_daily_ranks AS dr", "dr.volume_id", "=", "dv.id")
            ->join("users AS u", "u.id", "=", "dr.user_id")
            ->join("cm_ranks AS cr", "cr.id", "=", "dr.rank_id")
            ->join("cm_ranks AS pr", "pr.id", "=", "dr.paid_as_rank_id")
            ->leftJoin("users AS s", "s.id", "=", "u.sponsorid")
            ->selectRaw("
                dv.user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
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
            ")
            ->where('dv.volume_date', $start_date);

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


        if (!!$rank_id) {
            $query->where('dr.paid_as_rank_id', $rank_id);
        }

        return $query;
    }

    protected function getHighestQuery($user_id, $start_date, $end_date, $rank_id, $is_all, &$level = 0)
    {
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

        $query = DB::table('cm_achieved_ranks AS a')
            ->join("users AS u", "u.id", "=", "a.user_id")
            ->join("cm_ranks AS r", "r.id", "=", "a.rank_id")
            ->join("cm_daily_volumes AS dv", "dv.user_id", "=", "a.user_id")
            ->join("cm_daily_ranks AS dr", "dr.volume_id", "=", "dv.id")
            ->join("cm_ranks AS pr", "pr.id", "=", "dr.paid_as_rank_id")
            ->leftJoin("users AS s", "s.id", "=", "u.sponsorid")
            ->selectRaw("
                dv.user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                a.rank_id,
                r.name AS highest_rank,
                pr.name AS paid_as_rank,
                dv.level - $level AS level,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                a.date_achieved
            ")
             ->where('dv.volume_date', $end_date)
             //->whereBetween('dv.volume_date', [$start_date, $end_date])
            // ->where('a.date_achieved', '<=', $start_date)
            ->whereRaw('a.rank_id = (
                    SELECT aa.rank_id
                    FROM cm_achieved_ranks aa
                    WHERE aa.user_id = a.user_id AND aa.date_achieved <= ?
                    ORDER BY aa.rank_id DESC LIMIT 1
                )'
                , [$end_date]);

        if (!!$user_id) {

            $query->whereRaw("EXISTS(
                WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`
                    FROM users
                    WHERE id = ? AND levelid = 3
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE p.levelid = 3
                )
                SELECT 1 FROM downline d WHERE d.user_id = a.user_id
            )", [$user_id]);
        }

        if (!$is_all) {
            $query->whereBetween('a.date_achieved', [$start_date, $end_date]);
        }

        if (!!$rank_id) {
            $query->where("a.rank_id", $rank_id);
        }

        return $query;
    }

    public function getEnrollmentDownloadLink($start_date, $rank_id, $user_id = null)
    {
        $csv = new CsvReport(static::REPORT_PATH);

        if (!$start_date) {
            $data = [];
        } else {
            $data = $this->getEnrollmentQuery($user_id, $start_date, $rank_id)->get();
        }

        $filename = "rank-history-enrollment-tree-$start_date-";

        if ($user_id !== null) {
            $filename .= "$user_id-";
        }

        if(!!$rank_id) {
            $filename .= "$rank_id-";
        }

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }

    public function getHighestDownloadLink($start_date, $end_date, $rank_id, $is_all, $user_id = null)
    {
        $csv = new CsvReport(static::REPORT_PATH);

        if (!$start_date || !$end_date) {
            $data = [];
        } else {
            $data = $this->getHighestQuery($user_id, $start_date, $end_date, $rank_id, $is_all)->get();
        }

        $filename = "rank-history-";

        if($is_all) {
            $filename .= "all-highest-achieved-as-of-$end_date-";
        } else {
            $filename .= "highest-achieved-$start_date-$end_date-";
        }

        if ($user_id !== null) {
            $filename .= "$user_id-";
        }

        if(!!$rank_id) {
            $filename .= "$rank_id-";
        }

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }
}