<?php


namespace Commissions\Member;


use App\AchievedRank;
use App\Affiliate;
use App\CommissionPeriod;
use App\DailyVolume;
use App\Rank;
use App\DailyRank;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Commissions\CommissionTypes\TitleAchievementBonus;
use Commissions\VolumesAndRanks;
use Illuminate\Support\Facades\DB;

class Dashboard
{
    protected $db;
    protected $default_rank = 'Customer';

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getCurrentRankDetails($user_id)
    {
        $date = new Carbon();
        $today = $date->format('Y-m-d');

        $sql = "
        SELECT u.id,
            cr.name AS `name`,
            CASE 
                WHEN cdr.is_active = 1 THEN 'Yes'
            ELSE 'No'
            END AS is_active,
            cdv.pv,
            (select name from cm_ranks where id = (cdr.rank_id + 1)) AS next_rank_name,
            cdr.rank_id
        FROM cm_daily_ranks AS cdr
        LEFT JOIN users u ON u.id = cdr.user_id
        LEFT JOIN cm_ranks AS cr ON cr.id = cdr.paid_as_rank_id
        LEFT JOIN cm_daily_volumes AS cdv ON cdr.volume_id = cdv.id
        WHERE cdr.user_id = :user_id
        AND cdr.rank_date = :today
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":today", $today);
        $stmt->execute();

        $current_rank_data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($current_rank_data === false) {

            $enrolled_today = DB::table('cm_affiliates')
                ->whereRaw('affiliated_date = CURRENT_DATE()')
                ->where('user_id', $user_id)
                ->count();
            if(+$enrolled_today > 0)
            {
                $this->initNewUserRankVolumes($user_id);
                return $this->getCurrentRankDetails($user_id);
            }

            return [];
        }

        // Current rank data        
        $data['paid_as_rank'] = $this->isNullOrEmptyString($current_rank_data['name']) ? $this->default_rank : $current_rank_data['name'];

        $highest_rank_achieved = $this->getHighestRankAchieved($user_id);
        $data['highest_rank'] = $this->isNullOrEmptyString($highest_rank_achieved) ? $this->default_rank : $highest_rank_achieved;

        $current_rank = $this->getCurrentRank($user_id);
        $data['current_rank'] = $this->isNullOrEmptyString($current_rank) ? $this->default_rank : $current_rank;

        $data['is_active'] = $current_rank_data['is_active'];
        $data['business_volume'] = $current_rank_data['pv'];

        //$next_rank = $this->getNextRank($current_rank_data['rank_id']);
        $data['next_rank'] = $current_rank_data['next_rank_name'];
        
       // if(count($next_rank) > 0) {
       //     $data['next_rank'] = $current_rank_data['next_rank_name'];
       // }
        if($current_rank_data['rank_id'] < 12){
            $data['needs'] = VolumesAndRanks::getNextRankRequirementsByDailyVolume(DailyVolume::ofMember($user_id)->today()->first(), Rank::find($current_rank_data['rank_id']+1));
        }
        
        return $data;
    }

    public function getCurrentRank($user_id) 
    {
        $date = new Carbon();
        $today = $date->format('Y-m-d');

        $sql = "
            SELECT name AS `name`
            FROM cm_ranks r
            JOIN cm_daily_ranks dr ON r.id = dr.rank_id
            JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            WHERE dv.user_id = :user_id AND dv.volume_date = :date_today
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(":date_today", $today);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        return $result;
    }

    private function getHighestRankAchieved($user_id) 
    {
        $sql = "
            SELECT
            name AS `name`
            FROM cm_ranks
			WHERE id = (SELECT MAX(rank_id) FROM cm_achieved_ranks WHERE  user_id = :user_id)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        return $result;
    }

    private function getNextRank($user_id) 
    {
        // Date today
        $date_today = new Carbon();

        $rank = DailyRank::where('user_id', $user_id)
                ->where('rank_date', $date_today->format('Y-m-d'))
                ->first();

        
        if(count($rank) > 0) {
            if($rank['rank_id'] > 1) {
                $rank_id = $rank['rank_id'] + 1;
            } 
            elseif($rank['rank_id'] === 1) {
                $rank_id = 3; //default rank is customer
            }
        } else {
            $rank_id = 2;
        }

        $highest_rank = config('commission.ranks.global-trader');

        if($rank_id > 1 && $rank_id <= $highest_rank) {
            $next_rank = Rank::find($rank_id);
        } else {
            $next_rank = [];
        }

        return $next_rank;
    }

    private function isNullOrEmptyString($str) 
    {
        try {
            return (!isset($str) || trim($str) === '');
        } catch (\Exception $e) {
            return (is_null($str));
        }
    }

    public function getCurrentPeriodOrders($user_id, $filters)
    {
		$firstDay = date('Y-m-01');
		$today = date('Y-m-d');

        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;

        //default filters
        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];
        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');

        $user_id = +$user_id;

        $query = DB::table('v_cm_transactions AS t')
            ->selectRaw("
                t.transaction_id,
                t.invoice,
                t.user_id,
                CONCAT(u.fname, ' ', u.lname) purchaser,
                t.sponsor_id,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                (
                    SELECT
                        CONCAT('[', 
                            GROUP_CONCAT(JSON_OBJECT('quantity', tp.quantity, 'product', p.model)), 
                        ']') products
                    FROM transaction_products tp
                    JOIN oc_product p ON p.product_id = tp.shoppingcart_product_id
                    WHERE tp.transaction_id = t.transaction_id
                ) products,
                t.amount,
                t.transaction_date,
                getCommissionValue(t.transaction_id) AS bv
            ")
            ->join('users AS u', 'u.id', '=', 't.user_id')
            ->leftJoin('users AS s', 's.id', '=', 't.sponsor_id')
            ->where('t.type', 'product')
			->whereRaw("DATE(t.transaction_date) BETWEEN '".$firstDay."' AND '".$today."'")
            ->whereRaw("
                EXISTS(
                    WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                        SELECT 
                            u.id AS user_id,
                            u.sponsorid AS parent_id,
                            0 AS `level`
                        FROM users AS u
                        WHERE u.id = $user_id AND u.levelid = 3
                        
                        UNION ALL
                        
                        SELECT
                            u.id AS user_id,
                            u.sponsorid AS parent_id,
                            d.`level` + 1 `level`
                        FROM users u
                        JOIN downline AS d ON u.sponsorid = d.user_id
                        WHERE u.levelid = 3 AND d.`level` < 1
                        
                    )
                    SELECT 1 
                    FROM downline AS d 
                    WHERE d.user_id = t.user_id 
                        AND (
                            d.`level` <= 1 OR 
                            (d.`level` = 2 AND FIND_IN_SET(t.purchaser_catid, '$customers') AND FIND_IN_SET(t.sponsor_catid, '$affiliates'))
                        )
                )
            ");

        $recordsTotal = $query->count(DB::raw("1"));

        //apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('t.user_id', $search)
                    ->orWhere('t.sponsor_id', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('t.invoice', 'LIKE', "%{$search}%")
                    ->orWhere('t.transaction_date', 'LIKE', "%{$search}%")
                    ->orWhereRaw("EXISTS(
                        SELECT 1 
                        FROM transaction_products tp
                        JOIN oc_product p ON p.product_id = tp.shoppingcart_product_id
                        WHERE tp.transaction_id = t.transaction_id AND p.model LIKE ?)", ["%{$search}%"]
                    );
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by
        //  order by 1 column
        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir'])->orderBy('t.transaction_date', 'desc')->orderBy('t.transaction_id');
        } else {
            $query = $query->orderBy('t.transaction_date', 'desc')->orderBy('t.transaction_id');
        }

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getCurrentBinaryVolumeDetails($user_id)
    {
        $date_today = new Carbon();
        $result = DailyVolume::where('user_id', $user_id)
                ->where('volume_date', $date_today->format('Y-m-d'))
                ->first();

        if(count($result) === 0) {
            return [];
        }

        $data['left_leg_volume'] =  $result['total_group_volume_left_leg'];
        $data['left_leg_volume_today'] = $result['group_volume_left_leg'];
        $data['left_leg_rollover'] = $result['rollover_volume_left'];
        $data['right_leg_volume'] = $result['total_group_volume_right_leg'];
        $data['right_leg_volume_today'] = $result['group_volume_right_leg'];
        $data['right_leg_rollover'] = $result['rollover_volume_right'];

        return $data;
    }

    public function getEarningsDetails($user_id)
    {
        $earnings = [];
        $earnings['lifetime_earnings'] = $this->getLifeTimeEarnings($user_id);
        $earnings['last_week_earnings'] = $this->getWeeklyEarnings($user_id);

        return $earnings;
    }

    public function getLifeTimeEarnings($user_id)
    {
        $sql = "
            SELECT COALESCE(SUM(payouts.amount), 0.00) AS total_payout
            FROM cm_commission_payouts AS payouts
            LEFT JOIN cm_commission_periods AS periods ON periods.id = payouts.commission_period_id
            WHERE payouts.payee_id = :member_id
                AND periods.is_locked = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":member_id", $user_id);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['total_payout'];
    }

    public function getWeeklyEarnings($user_id)
    {
        $sql = "
            SELECT COALESCE(SUM(payouts.amount), 0.00) AS total_payout
            FROM cm_commission_payouts AS payouts
            LEFT JOIN cm_commission_periods AS periods ON periods.id = payouts.commission_period_id
            LEFT JOIN cm_commission_types AS comm_type ON periods.commission_type_id = comm_type.id
            WHERE comm_type.frequency = 'weekly'
                AND payouts.payee_id = :member_id
                AND periods.is_locked = 1
                AND DATEDIFF(NOW(), periods.end_date) < 14
            ORDER BY periods.start_date DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":member_id", $user_id);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['total_payout'];
    }

    /**
     * @param $user_id
     */
    private function initNewUserRankVolumes($user_id)
    {
        $today = Carbon::today()->format('Y-m-d');

        $active_ibo_id = config('commission.ranks.ibo');
        $active_ibo = Rank::find($active_ibo_id);

        $v = DailyVolume::updateOrCreate(
            ['user_id'=>$user_id
            ,'volume_date' => $today
            ]
        );

        $pv = DB::table('v_cm_transactions as t')
            ->selectRaw('SUM(computed_cv) as pv')
            ->where('t.user_id', $user_id)
            ->first()->pv;


        $v->pv = $pv;
        $v->volume_date = $today;
        $v->save();

        $r = DailyRank::updateOrCreate([
            'volume_id'     => $v->id
            , 'rank_date'   => $today
            , 'is_active'   => 1
            , 'user_id'     =>$user_id
        ]);


        if($v->pv >= $active_ibo->pv_requirement)
        {
            $r->rank_id = $active_ibo_id;
            $r->paid_as_rank_id = $active_ibo_id;
        }

        $r->save();

        $highest_rank = 'INSERT INTO cm_achieved_ranks (user_id, rank_id, date_achieved, recent_date_achieved, reachieved_date) values (
            :uid
            , :rid
            , :da
            , :rda
            , :rd)';

        $s = $this->db->prepare($highest_rank);
        $s->bindParam('uid', $user_id);
        $s->bindParam('rid', $active_ibo_id);
        $s->bindParam('da', $today);
        $s->bindParam('rda', $today);
        $s->bindParam('rd', $today);

        $s->execute();

        return;
    }
}