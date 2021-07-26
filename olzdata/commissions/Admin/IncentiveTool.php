<?php

namespace Commissions\Admin;

use Carbon\Carbon;
use Exception;
use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;
use App\IncentiveTool as ITS;
use App\ArbitraryPoints;
use App\Rank;
use \PDO;


class IncentiveTool
{
    const REPORT_PATH = "csv/admin/incentive_tool";
    protected $db;
    protected $today;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->today = date("Y-m-d");
    }

    public function getRunningIncentive($filters)
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

        $query = DB::table('cm_incentive_tool_settings as cits')
            ->selectRaw("
                cits.id as settings_id,
                cits.title,
                CONCAT(start_date, ' - ', end_date) AS period
            ")
            ->where("cits.is_active", "=", 1)
            ->where("cits.is_locked", "=", 0)
            ->where("cits.is_display_insentives", "=", 1)
            ->where("cits.start_date", "<", $this->today)
            ->where("cits.end_date", ">=", $this->today);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('cits.title', 'LIKE', "%{$search}%")
                    ->orWhere('cits.start_date', 'LIKE', "%{$search}%")
                    ->orWhere('cits.end_date', 'LIKE', "%{$search}%")
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

        // default order by
        $query = $query->orderBy('cits.id', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getClosedIncentive($filters)
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
        

        $query = DB::table('cm_incentive_tool_settings as cits')
            ->selectRaw("
                cits.id AS settings_id,
                cits.title,
                CONCAT(start_date, ' - ', end_date) AS period
            ")
            ->where("cits.is_active", "0")
            ->where("cits.is_locked", "1")
            ->where("is_display_insentives", "1")
            ->where("cits.end_date", "<", $this->today);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('cits.title', 'LIKE', "%{$search}%")
                    ->orWhere('cits.start_date', 'LIKE', "%{$search}%")
                    ->orWhere('cits.end_date', 'LIKE', "%{$search}%")
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

        // default order by
        $query = $query->orderBy('cits.id', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getArbitraryBonus($filters)
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

        $query = DB::table('users AS u')
            ->selectRaw("
                cap.id,
                cdip.user_id,
                CONCAT(u.fname, ' ', u.lname) AS `name`, 
                cits.`title`, 
                cdip.points, 
                cap.bonus_points,
                (cdip.points + cdip.bonus_points) AS total
            ")
            ->join("cm_daily_incentive_points AS cdip", "u.id", "=", "cdip.user_id")
            ->join("cm_arbitrary_points AS cap", "cap.user_id", "=", "cdip.user_id")
            ->join('cm_incentive_tool_settings AS cits', function ($join) {
                $join->on('cits.id', '=', 'cdip.settings_id')->on('cits.id', '=', 'cap.settings_id');
            })
            ->where("cits.is_active", 1)
            ->where("cits.is_locked", 0)
            ->where("cdip.incentive_point_date", "=", $this->today);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('cits.title', 'LIKE', "%{$search}%")
                    ->orWhere('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('cdip.points', 'LIKE', "%{$search}%")
                    ->orWhere('cap.bonus_points', 'LIKE', "%{$search}%")
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

        // default order by
        $query = $query->orderBy('cits.title', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getAllRepresentativeByIncentive($filters, $id, $is_active, $is_locked)
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
        $settings_id = $id;

        $tempQuery = DB::table('users AS u')
            ->selectRaw("
                cdip.id, 
                u.id AS user_id, 
                CONCAT(u.fname, ' ', u.lname) AS `name`, 
                SUM(cdip.points) AS points, 
                SUM(cdip.bonus_points) as bonus_points
            ")
            ->join("cm_daily_incentive_points AS cdip", "u.id", "=", "cdip.user_id")
            ->join("cm_incentive_tool_settings AS cits", "cits.id", "=", "cdip.settings_id")
            ->where("cits.is_active", $is_active)
            ->where("cits.is_locked", $is_locked)
            ->where("cits.id", $settings_id)
            ->groupBy('cdip.user_id')
            ->get();
        $recordsTotal = count($tempQuery);

        $query = DB::table('users AS u')
            ->selectRaw("
                cdip.id, 
                u.id AS user_id, 
                CONCAT(u.fname, ' ', u.lname) AS `name`, 
                SUM(cdip.points) AS points, 
                SUM(cdip.bonus_points) as bonus_points
            ")
            ->join("cm_daily_incentive_points AS cdip", "u.id", "=", "cdip.user_id")
            ->join("cm_incentive_tool_settings AS cits", "cits.id", "=", "cdip.settings_id")
            ->where("cits.is_active", $is_active)
            ->where("cits.is_locked", $is_locked)
            ->where("cits.id", $settings_id);

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->Where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%");
            });
        }


        $query->groupBy("cdip.user_id");

        $recordsFiltered = count($query->get());

        // apply order by
        // order by 1 column

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
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

    public function getRanks()
    {
        return Rank::all();
    }

    public function getRepresentatives($request)
    {
        
            $filter = $request['f'];
            $query = $request['q'];

            // throw new \ErrorException($filter . ' ' . $query . ' ' . $memberid);

            $sql = "
                SELECT CONCAT(u.fname, ' ', u.lname) AS `name`, cdip.user_id, u.site
                FROM users u
                JOIN cm_daily_incentive_points cdip ON u.id = cdip.`user_id`
                WHERE cdip.`incentive_point_date` = CURRENT_DATE()
            ";
            switch ($filter) {
                case 'id':
                    $sql .= " AND cdip.user_id = $query";
                    break;
                case 'fname':
                    $sql .= " AND u.fname LIKE '%$query%'";
                    break;
                case 'lname':
                    $sql .= " AND u.lname LIKE '%$query%'";
                    break;
            }

        $sql .= " GROUP BY cdip.user_id"; 

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = array();
            foreach ($members as $key => $member) {
                $results[] = array(
                    "value" => $member['name'],
                    "site" => $member['site'],
                    "user_id" => $member['user_id'],
                    "display" => '#' . $member['user_id'] . ': ' . $member['name'],
                );
            }

        return $results;
    }

    public function getOpenIncentives()
    {
        $sql = "
            SELECT id, title FROM cm_incentive_tool_settings
            WHERE is_active = 1 AND is_locked = 0 AND end_date >= CURRENT_DATE()
            ORDER by id ASC;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $incentives = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $incentives;
    }

    public function getIncentiveSettings($id)
    {
         return ITS::findOrFail($id);
    }

    public function download($settings_id)
    {
        
        $csv = new CsvReport(static::REPORT_PATH);
        
        if(!$settings_id) {
            $data = [];
            $filename = "incentive_tool_settings_$settings_id";
        } 
        else {
            $query = DB::table('cm_daily_incentive_points')
                    ->selectRaw("
                        user_id, 
                        settings_id, 
                        volume_id, 
                        prs, 
                        points, 
                        bonus_points,
                        (points + bonus_points) AS total_points,
                        incentive_point_date
                    ")
                    ->where("settings_id", $settings_id)
                    ->groupBy("incentive_point_date", "user_id")
                    ->orderBy("incentive_point_date", "DESC");

            $data = $query->get();
            
            $settings = ITS::findOrFail($settings_id);    
            $filename = "Incentive_tool_settings-".$settings['title'];

        }

        $filename .= "-".time();
    
        return $csv->generateLink($filename, $data);
    }

    public function saveIncentive($data)
    {
        $settings = new ITS();
        $settings->title = $data['title'];
        $settings->description = $data['description'];
        $settings->start_date = $data['start_date'];
        $settings->end_date = $data['end_date'];
        $settings->is_display_insentives = $data['is_display_insentives'];
        $settings->is_double_points_on = $data['is_double_points_on'];
        $settings->double_points_start_date = $data['double_points_start_date'];
        $settings->double_points_end_date = $data['double_points_end_date'];
        $settings->is_points_per_prs = $data['is_points_per_prs'];
        $settings->points_per_prs = $data['points_per_prs'];
        $settings->is_promote_to_or_higher = $data['is_promote_to_or_higher'];
        $settings->promote_to_or_higher_points = $data['promote_to_or_higher_points'];
        $settings->rank_id = $data['rank_id'];
        $settings->is_has_new_representative = $data['is_has_new_representative'];
        $settings->new_representative_points = $data['new_representative_points'];
        $settings->new_representative_start_date = $data['new_representative_start_date'];
        $settings->new_representative_end_date = $data['new_representative_end_date'];
        $settings->new_representative_min_prs = $data['new_representative_min_prs'];
        $settings->new_representative_first_n_days = $data['new_representative_first_n_days'];
        $settings->is_double_points_new_representative = $data['is_double_points_new_representative'];
        $settings->double_points_new_representative_start_date = $data['double_points_new_representative_start_date'];
        $settings->double_points_new_representative_end_date = $data['double_points_new_representative_end_date'];
        $settings->double_points_new_representative_first_n_days = $data['double_points_new_representative_first_n_days'];
        $settings->is_active = 1;
        $settings->save();
    }

    public function updateIncentive($data)
    {
        $settings = ITS::findOrFail($data['id']);

        $settings->title = $data['title'];
        $settings->description = $data['description'];
        $settings->start_date = $data['start_date'];
        $settings->end_date = $data['end_date'];
        $settings->is_display_insentives = $data['is_display_insentives'];
        $settings->is_double_points_on = $data['is_double_points_on'];
        $settings->double_points_start_date = $data['double_points_start_date'];
        $settings->double_points_end_date = $data['double_points_end_date'];
        $settings->is_points_per_prs = $data['is_points_per_prs'];
        $settings->points_per_prs = $data['points_per_prs'];
        $settings->is_promote_to_or_higher = $data['is_promote_to_or_higher'];
        $settings->promote_to_or_higher_points = $data['promote_to_or_higher_points'];
        $settings->rank_id = $data['rank_id'];
        $settings->is_has_new_representative = $data['is_has_new_representative'];
        $settings->new_representative_points = $data['new_representative_points'];
        $settings->new_representative_start_date = $data['new_representative_start_date'];
        $settings->new_representative_end_date = $data['new_representative_end_date'];
        $settings->new_representative_min_prs = $data['new_representative_min_prs'];
        $settings->new_representative_first_n_days = $data['new_representative_first_n_days'];
        $settings->is_double_points_new_representative = $data['is_double_points_new_representative'];
        $settings->double_points_new_representative_start_date = $data['double_points_new_representative_start_date'];
        $settings->double_points_new_representative_end_date = $data['double_points_new_representative_end_date'];
        $settings->double_points_new_representative_first_n_days = $data['double_points_new_representative_first_n_days'];
        $settings->is_active = 1;
        $settings->save();
    }

    public function deleteIncentive($id)
    {
        $data = ITS::findOrFail($id);

        if(count($data) > 0) {
            $data->delete();

            DB::table('cm_daily_incentive_points')->where('settings_id', '=', $id)->delete();
        }
    }

    public function hideIncentive($id)
    {
        $data = ITS::findOrFail($id);
        $data->is_display_insentives = 0;
        $data->save();
    }

    public function deleteArbitraryPoints($id)
    {
        $arbitrary = ArbitraryPoints::findOrFail($id);
        if(count($arbitrary) > 0) {

            //update
            $rep_points = DB::table('cm_daily_incentive_points')
                ->select("id", "points", "bonus_points")
                ->where('settings_id', '=', $arbitrary['settings_id'])
                ->where('user_id', '=', $arbitrary['user_id'])
                ->where('incentive_point_date', '=', $this->today)
                ->get();

            foreach($rep_points as $rep) {

                $bonus_points = $rep->bonus_points - $arbitrary['bonus_points'];
                if($bonus_points < 0) {
                    $bonus_points = 0;
                }

                $affected = DB::table('cm_daily_incentive_points')
                ->where('id', $rep->id)
                ->update(['bonus_points' => $bonus_points]);

            }

            //delete
            
            $arbitrary->delete();
            return 'Successfully Deleted';
        }
    }

    public function addArbitraryPoints($data)
    {
        // save arbitrary
        $bonus = new ArbitraryPoints();
        $bonus->user_id = $data['user_id'];
        $bonus->settings_id = $data['settings_id'];
        $bonus->bonus_points = $data['bonus_points'];
        $bonus->save();

        // update

        $sql = "
            UPDATE cm_daily_incentive_points cdip
            SET cdip.`bonus_points` = cdip.`bonus_points` + :bonus_points
            WHERE cdip.`incentive_point_date` = CURRENT_DATE()
            AND cdip.`user_id` = :user_id
            AND cdip.settings_id = :settings_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':bonus_points', $data['bonus_points']);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':settings_id', $data['settings_id']);
        $stmt->execute();

        return "Successfully Added";
    }
}