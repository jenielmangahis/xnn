<?php


namespace Commissions\Member;

use Carbon\Carbon;
use Commissions\QueryHelper;
use Illuminate\Support\Facades\DB;
use \Illuminate\Database\Capsule\Manager;
use \Illuminate\Support\Facades\Config;
use PDO;

class IncentiveReport
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getAvailableIncentive()
    {
        return DB::table('cm_incentive_tool_settings')
            ->selectRaw("
                id,
                title
            ")
            ->where("is_active", 1)
            ->where("is_display_insentives", 1)
            ->where("end_date", ">=","CURRENT_DATE()")
            ->get();
    }

    public function getProgress($filters, $member_id)
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
        $incentive_id = $filters['incentiveId'];

        if($incentive_id == 0)
        {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = DB::table('cm_daily_incentive_points AS p')
            ->join(DB::raw(
                "
                (
                    WITH RECURSIVE downline (user_id, parent_id, level) AS (
                    SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    0 AS level
                    FROM users
                    WHERE id = $member_id
                    AND levelid = 3
                    
                    UNION ALL
                    
                    SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    downline.level + 1 level
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE p.levelid = 3
                ) SELECT * FROM downline) as d 
                "
            ), "d.user_id", '=', 'p.user_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->join('users as s', 's.id', '=', 'u.sponsorid')
            ->where('p.settings_id', $incentive_id)
            ->whereRaw('p.incentive_point_date = (SELECT MAX(incentive_point_date) FROM cm_daily_incentive_points where settings_id = ?)', $incentive_id)
            ->selectRaw("
                d.user_id,
                CONCAT(u.fname, ' ', u.lname) as member_name,
                CONCAT(s.fname, ' ', s.lname) as sponsor_name,
                d.level,
                p.points
            ")
        ;


        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by
        // order by 1 column

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }
        else{
            $query = $query->orderBy('d.level', 'ASC');
        }

        // default order by
        $query = $query->orderBy('u.id', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');

    }

}


