<?php


namespace Commissions\Member;


use App\AchievedRank;
use App\Affiliate;
use App\CommissionPeriod;
use App\DailyVolume;
use App\Rank;
use App\User;
use Carbon\Carbon;
use Commissions\CommissionTypes\TitleAchievementBonus;
use Commissions\VolumesAndRanks;
use Illuminate\Support\Facades\DB;

class Dashboard
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getTitleAchievementBonusDetails($user_id)
    {
        $highest = AchievedRank::ofMember($user_id)->highest()->first();

        $highest_rank_id = 1;

        if ($highest !== null) {
            $highest_rank_id = $highest->rank_id;
        }

        $max = 41;

        for ($i = 9; $i <= 41; $i = $i + 4) {
            if ($highest_rank_id < $i) {
                $max = $i;
                break;
            }
        }

        $min = $max - 4;

        $ranks = Rank::whereBetween('id', [$min, $max])->orderBy('id', 'asc')->get();

        $next_rank_id = $highest_rank_id < 5 ? 5 : $highest_rank_id + 1;

        $next_bonus = TitleAchievementBonus::getBonus($next_rank_id);

        $double_bonus = $this->getDoubleFastStartDetails($user_id, $next_rank_id);

        return compact('ranks', 'next_bonus', 'highest_rank_id', 'highest', 'max', 'min', 'double_bonus');
    }

    public function getCurrentRankDetails($user_id)
    {
        $default_affiliate = config('commission.affiliate');

        /*$sql = "
            SELECT
                u.id AS user_id,
                dr.rank_id,
                dr.paid_as_rank_id,
                IFNULL(h.name, '$default_affiliate') AS highest_rank,
                IFNULL(r.name, '$default_affiliate') AS paid_as_rank,
                IFNULL(c.name, '$default_affiliate') AS current_rank,
                IFNULL(dr.is_active, 0) AS is_active,
                IFNULL(dv.preferred_customer_count, 0) AS preferred_customer_count,
                IFNULL(dv.referral_points, 0) AS referral_points,
                IFNULL(dv.coach_points, 0) AS coach_points,
                IFNULL(dv.organization_points, 0) AS organization_points,
                IFNULL(dv.team_group_points, 0) AS team_group_points,
                IFNULL(dv.influencer_count, 0) AS influencer_count,
                IFNULL(dv.silver_influencer_count, 0) AS silver_influencer_count,
                IFNULL(dv.gold_influencer_count, 0) AS gold_influencer_count,
                IFNULL(dv.platinum_influencer_count, 0) AS platinum_influencer_count,
                IFNULL(dv.diamond_influencer_count, 0) AS diamond_influencer_count,
                IFNULL(n.name, '$default_affiliate') next_rank,
                dv.referral_preferred_customer_users,
                dv.referral_enrolled_coach_users,
                dv.referral_rank_advancement_users,
                n.id AS next_rank_id
            FROM users u
            LEFT JOIN cm_daily_volumes dv ON dv.user_id = u.id AND dv.volume_date = CURRENT_DATE()
            LEFT JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
            LEFT JOIN cm_achieved_ranks ar ON ar.user_id = dr.user_id 
                AND ar.rank_id = (
                    SELECT ar_.rank_id 
                    FROM cm_achieved_ranks ar_ 
                    WHERE ar_.user_id = ar.user_id 
                    ORDER BY ar_.rank_id DESC 
                    LIMIT 1
                )
            LEFT JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
            LEFT JOIN cm_ranks c ON c.id = dr.rank_id
            LEFT JOIN cm_ranks h ON h.id = ar.rank_id
            LEFT JOIN cm_ranks n ON n.id = dr.paid_as_rank_id + 1
            WHERE u.id = :user_id
            LIMIT 1;
        ";*/

        $sql = "
            SELECT
                u.id AS user_id,
                dr.rank_id,
                dr.paid_as_rank_id,
                IFNULL(h.name, '$default_affiliate') AS highest_rank,
                IFNULL(r.name, '$default_affiliate') AS paid_as_rank,
                IFNULL(c.name, '$default_affiliate') AS current_rank,
                IFNULL(dr.is_active, 0) AS is_active,
                IFNULL(n.name, '$default_affiliate') next_rank,
                dv.prs AS volume_prs,
                dv.grs AS volume_grs,
                dv.sponsored_qualified_representatives_count AS sponsored_qualified_representatives,
                dv.sponsored_leader_or_higher_count AS sponsored_leader_or_higher,
                n.id AS next_rank_id,
                (n.prs_requirement - dv.prs) AS needs_prs
            FROM users u
            LEFT JOIN cm_daily_volumes dv ON dv.user_id = u.id AND dv.volume_date = CURRENT_DATE()
            LEFT JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
            LEFT JOIN cm_achieved_ranks ar ON ar.user_id = dr.user_id 
                AND ar.rank_id = (
                    SELECT ar_.rank_id 
                    FROM cm_achieved_ranks ar_ 
                    WHERE ar_.user_id = ar.user_id 
                    ORDER BY ar_.rank_id DESC 
                    LIMIT 1
                )
            LEFT JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
            LEFT JOIN cm_ranks c ON c.id = dr.rank_id
            LEFT JOIN cm_ranks h ON h.id = ar.rank_id
            LEFT JOIN cm_ranks n ON n.id = dr.paid_as_rank_id + 1
            WHERE u.id = :user_id
            LIMIT 1;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result === false) {
            return [];
        }


        // $result['debug'] = compact('referral_preferred_customer_users', 'referral_enrolled_coach_users', 'referral_rank_advancement_users');

        try {
            $result['referral_points_details'] = $this->getReferralPointsDetails($result);
        } catch(\Exception $ex) {
            $result['referral_points_details'] = [];
        }

        if ($result['next_rank_id'] !== null) {
            $result['needs'] = VolumesAndRanks::getNextRankRequirementsByDailyVolume(DailyVolume::ofMember($user_id)->today()->first(), Rank::find($result['next_rank_id']));
        } else {
            $result['needs'] = [];
        }

        return $result;
    }

    protected function getReferralPointsDetails($result)
    {
        $referral_points = [];

        $referral_preferred_customer_users = collect(json_decode($result['referral_preferred_customer_users'], true))->where('points', '>', 0);
        $referral_enrolled_coach_users = collect(json_decode($result['referral_enrolled_coach_users'], true))->where('points', '>', 0);
        $referral_rank_advancement_users = collect(json_decode($result['referral_rank_advancement_users'], true))->where('points', '>', 0);

        foreach ($referral_preferred_customer_users as $user) {
            $info = $this->getUserInfo($user['user_id']);

            if ($info === null) continue;

            $referral_points[] = [
                'user_id' => $user['user_id'],
                'name' => $info->fname . ' ' . $info->lname,
                'type' => 'Enrolled Preferred Customer',
                'points' => $user['points'],
                'other_details' => ''
            ];
        }

        foreach ($referral_enrolled_coach_users as $user) {
            $info = $this->getUserInfo($user['user_id']);

            if ($info === null) continue;

            $other_details = 'Preferred Customers: ' . $user['preferred_customer_count'];

            if (+$user['has_upgraded']) {
                $other_details .= ', Upgraded from Customer: Yes';
            }

            $referral_points[] = [
                'user_id' => $user['user_id'],
                'name' => $info->fname . ' ' . $info->lname,
                'type' => 'Enrolled Coach',
                'points' => $user['points'],
                'other_details' => $other_details
            ];
        }

        foreach ($referral_rank_advancement_users as $user) {
            $info = $this->getUserInfo($user['user_id']);

            if ($info === null) continue;

            $rank = $this->getRankDetails(+$user['achieved_rank_id']);

            if ($rank === null) continue;

            $other_details = 'Rank: '. $rank->name;

            $referral_points[] = [
                'user_id' => $user['user_id'],
                'name' => $info->fname . ' ' . $info->lname,
                'type' => 'Rank Advancement',
                'points' => $user['points'],
                'other_details' => $other_details
            ];
        }

        return $referral_points;
    }

    protected $getUserInfoCache = [];

    protected function getUserInfo($user_id)
    {
        if (!array_key_exists($user_id, $this->getUserInfoCache)) {
            $this->getUserInfoCache[$user_id] = User::find($user_id);
        }

        return $this->getUserInfoCache[$user_id];
    }

    protected $getRankDetailsCache = [];

    protected function getRankDetails($rank_id)
    {
        if (!array_key_exists($rank_id, $this->getRankDetailsCache)) {
            $this->getRankDetailsCache[$rank_id] = Rank::find($rank_id);
        }

        return $this->getRankDetailsCache[$rank_id];
    }

    public function getCurrentPeriodOrders($user_id, $filters)
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
                getCappedVolume(t.user_id, t.transaction_id, t.transaction_date) AS cv
            ")
            ->join('users AS u', 'u.id', '=', 't.user_id')
            ->leftJoin('users AS s', 's.id', '=', 't.sponsor_id')
            ->where('t.type', 'product')
            ->whereRaw("
                EXISTS(
                    SELECT 1 
                    FROM cm_commission_periods pr 
                    WHERE pr.is_locked = 0 
                        AND t.transaction_date BETWEEN pr.start_date AND pr.end_date
                )
            ")
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

        // apply search
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

    public function getGiftCards($user_id, $filters)
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

        $user_id = +$user_id;

        $query = DB::table('gift_cards AS gc')
            ->selectRaw("
                gc.code,
                gc.validationcode AS validation_code,
                gc.amount,
                gc.balance,
                gc.end_date,
                gc.datecreated AS created_date
            ")
            ->where('gc.status', 1)
            ->where('gc.userid', $user_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('gc.amount', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('gc.code', 'LIKE', "%{$search}%")
                    ->orWhere('gc.validationcode', 'LIKE', "%{$search}%")
                    ->orWhere('gc.datecreated', 'LIKE', "%{$search}%")
                    ->orWhere('gc.end_date', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by
        //  order by 1 column
        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query = $query->orderBy('gc.code');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    private function getDoubleFastStartDetails($user_id, $next_rank_id)
    {
        $affiliate = Affiliate::findOrFail($user_id);

        $date = Carbon::createFromFormat("Y-m-d", $affiliate->affiliated_date);

        $enrollment_months = [
            5 => 2,
            6 => 3,
            7 => 4,
            8 => 5,
            9 => 6,
            10 => 7,
            11 => 8,
            12 => 9,
            13 => 10,
            14 => 12,

            15 => 1,
            16 => 2,
            17 => 3,
            18 => 4,
            19 => 5,
            20 => 6,
            21 => 8,
            22 => 10,
            23 => 12,
        ];

        if ($next_rank_id > 14) {
            $silver_influencer_1 = AchievedRank::ofMember($user_id)->rank(14)->first();
            $date = Carbon::createFromFormat("Y-m-d", $silver_influencer_1->date_achieved);
        }

        $days = 0;
        $hours = 0;
        $next_double_rank_id = 0;
        $next_double_rank_name = null;

        $debug = null;

        foreach ($enrollment_months as $rank_id => $month) {

            if ($next_rank_id > $rank_id) continue;

            $now = Carbon::now();

            $end_date = $date->startOfMonth()->addMonths($month)->endOfMonth();

            // $end_date = Carbon::now()->endOfDay();

            if ($now->greaterThan($end_date)) continue;

            $days = $now->diffInDays($end_date);
            $hours = $now->diffInHours($end_date);

            $next_double_rank_id = $rank_id;

            break;
        }

        $rank = Rank::find($next_double_rank_id);

        if ($rank !== null) {
            $next_double_rank_name = $rank->name;
        }

        return compact('days', 'next_double_rank_id', 'next_double_rank_name', 'next_rank_id', 'date', 'debug', 'hours');
    }


}