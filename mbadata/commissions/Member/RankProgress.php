<?php


namespace Commissions\Member;


use App\DailyVolume;
use App\Rank;
use Commissions\VolumesAndRanks;
use Illuminate\Support\Facades\DB;

class RankProgress
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getProgress($filters, $user_id = null)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $rank_id = isset($filters['rank_id']) ? +$filters['rank_id'] : null;
        $is_all_below = isset($filters['is_all_below']) ? +$filters['is_all_below'] : 0;

        if (!$rank_id) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $rank = Rank::find($rank_id);

        if ($rank === null) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $level = 0;

        if (!!$user_id) {

            $volume = DailyVolume::ofMember($user_id)->today()->first();

            $level = $volume === null ? 0 : +$volume->level;
        }

        $paid_as_rank_id = $rank_id - 1;

        if ($paid_as_rank_id < 1) {
            $paid_as_rank_id = 1;
        }

        $v = DailyVolume::orderBy('id', 'desc')->first();

        $query = DailyVolume::join("cm_daily_ranks AS dr", "dr.volume_id", "=", "cm_daily_volumes.id")
            ->join("users AS u", "u.id", "=", "dr.user_id")
            ->join("cm_ranks AS cr", "cr.id", "=", "dr.rank_id")
            ->join("cm_ranks AS pr", "pr.id", "=", "dr.paid_as_rank_id")
            ->leftJoin("users AS s", "s.id", "=", "u.sponsorid")
            ->selectRaw("
                cm_daily_volumes.*,
                CONCAT(u.fname, ' ', u.lname) AS member,
                dr.rank_id,
                cr.name AS current_rank,
                dr.paid_as_rank_id,
                pr.name AS paid_as_rank,
                dr.is_active,
                cm_daily_volumes.level - $level AS level,
                u.sponsorid AS sponsor_id,
                CONCAT(s.fname, ' ', s.lname) AS sponsor,
                dr.rank_date
                
            ")
            ->where('cm_daily_volumes.volume_date', $v->volume_date);

            #->whereRaw('cm_daily_volumes.volume_date = CURRENT_DATE()');

        if(!!$user_id) {
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
                        AND downline.level < 2
                )
                SELECT 1 FROM downline d WHERE d.user_id = cm_daily_volumes.user_id
            )", [$user_id]);
        }

        if ($is_all_below) {
            $query->where('dr.paid_as_rank_id', '<=', $paid_as_rank_id);
        } else {
            $query->where('dr.paid_as_rank_id', $paid_as_rank_id);
        }

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search, $level) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search)
                    ->orWhereRaw("cm_daily_volumes.level - $level = ?", [$search]);
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

        $query->orderBy("cm_daily_volumes.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        $data->map(function ($volume, $key) use ($rank) {
            $volume->needs = VolumesAndRanks::getNextRankRequirementsByDailyVolume($volume, $rank);
            return $volume;
        });

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }
}