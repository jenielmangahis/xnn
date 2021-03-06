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
                n.id AS next_rank_id,
                dv.pea,
                dv.ta - dv.pea AS ta,
                dv.qta,
                dv.mar
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
            LEFT JOIN cm_ranks n ON n.id = dr.rank_id + 1
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

        $result['current_rank_deets'] = VolumesAndRanks::getCurrentRankRequirementsByDailyVolume(DailyVolume::ofMember($user_id)->today()->first(), Rank::find($result['rank_id']));

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

    public function getPEA($user_id, $filters)
    {
        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];
        $search = $filters['search'];
        $order = isset($filters['order'])? $filters['order']:[];
        $columns = $filters['columns'];

        $query = DB::table('customers as u')
            ->join('cm_energy_accounts as cea','cea.customer_id','=','u.id')
            ->join('cm_energy_types as cet','cet.id','=','cea.energy_type')
            ->join('cm_energy_account_types as ceat','ceat.id','=','cea.account_type')
            ->join('cm_energy_account_status_types as ceast','ceast.id','=','cea.status')
            ->selectRaw("
            cea.id as energy_account_id,
            cea.plank_energy_account_id AS plank_energy_account_id,
            cea.customer_id AS user_id,
            cea.reference_id,
			CONCAT(REPEAT('*', CHAR_LENGTH(cea.reference_id) - 4), SUBSTR(cea.reference_id, CHAR_LENGTH(cea.reference_id) - 4)) AS por,
            IF(fname <> '', CONCAT(fname, ' ', LEFT(lname, 1), '.'), CONCAT(SUBSTR(business, 1, 5),'***')) AS customer,
            cet.display_text AS account,
            ceast.display_text AS status,
            (SELECT 
                    created_at 
            FROM cm_energy_account_logs 
            WHERE energy_account_id = cea.id
                AND current_status IN (4) 
            ORDER BY created_at ASC LIMIT 1) AS date_accepted,
            (SELECT 
                    created_at 
            FROM cm_energy_account_logs 
            WHERE energy_account_id = cea.id 
                AND current_status IN (5) 
            ORDER BY created_at ASC LIMIT 1) AS date_started_flowing,
            (SELECT
                ceaf.flowing_date
            FROM cm_energy_account_flowing ceaf
            WHERE ceaf.plank_energy_account_id = cea.plank_energy_account_id
            AND ceaf.id = (
                SELECT
                    ceaf1.id
                FROM cm_energy_account_flowing ceaf1
                WHERE ceaf1.plank_energy_account_id = cea.plank_energy_account_id
                ORDER BY ceaf1.created_at DESC LIMIT 1
                )
            ) AS flowing_date
            ")
            ->where('cea.sponsor_id', $user_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (!!$search) {
                $query->where(function ($query) use ($search) {
                    $search = strtolower($search);
                    $query->Where('u.fname', 'LIKE', "%{$search}%")
                        ->orWhere('u.lname', 'LIKE', "%{$search}%")
                        ->orWhere('cea.reference_id', 'LIKE', "%{$search}%")
                        ->orWhere('cet.display_text', 'LIKE', "%{$search}%")
                        ->orWhere('ceast.display_text', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT('#', u.id, ': ', u.fname, ' ', u.lname) LIKE ?", ["%{$search}%"]);

                    if(stripos('trasmesso', $search) !== false) {
                        $query->orWhere("ceast.id", 1);
                    }
                    if(stripos('da attivare', $search) !== false){
                        $query->orWhere("ceast.id", 2);
                    }
                    if(stripos('da verificare', $search) !== false){
                        $query->orWhere("ceast.id", 3);
                    }
                    if(stripos('in attesa di inizio fornitura', $search) !== false){
                        $query->orWhere("ceast.id", 4);
                    }
                    if(stripos('attivo', $search) !== false) {
                        $query->orWhere("ceast.id", 5);
                    }
                    if(stripos('attivo, in fase di cessazione', $search) !== false){
                        $query->orWhere("ceast.id", 6);
                    }
                    if(stripos('cessato', $search) !== false) {
                        $query->orWhere("ceast.id", 7);
                    }
                    if(stripos('voltura in corso', $search) !== false){
                        $query->orWhereRaw("EXISTS(SELECT 1 FROM cm_energy_account_status_types_details ceastd WHERE ceastd.parent_status_type = cea.status AND ceastd.type = 42)");
                    }
                    if(stripos('attivato', $search) !== false){
                        $query->orWhereRaw("EXISTS(SELECT 1 FROM cm_energy_account_status_types_details ceastd WHERE ceastd.parent_status_type = cea.status AND FIND_IN_SET(ceastd.type, '17,37,47'))");
                    }
                    if(stripos('luce', $search) !== false) {
                        $query->orWhere("cet.display_text", "Electric");
                    }
                });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }
        else
        {
            $query = $query->orderBy('cea.id','DESC');
        }
        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getPEAStatusHistory($user_id, $request)
    {
		$peaID = $request['energy_account_id'];
        $sql = "
        SELECT 
            ceat.display_text AS status,
            DATE_FORMAT(ceal.created_at, '%d/%m/%Y') AS date
        FROM cm_energy_account_logs ceal
        INNER JOIN cm_energy_account_status_types ceat ON ceat.id = ceal.current_status
        WHERE ceal.energy_account_id = :energy_account_id
        ORDER BY ceal.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":energy_account_id", $peaID);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['data' => $result];
    }

    public function getLastThreeMonthsEarnings($user_id)
    {
        $ret = [];
        for($i = 1; $i < 4; $i++) { # for each month
            $tmp = date('Y-m-15'); // Get the middle of the month to avoid PHP date bug.
            $begin_date = date('Y-m-01', strtotime($tmp . '-'.$i.' month')); // First day of calendar month in future.
            $end_date = date('Y-m-t', strtotime($begin_date));
            $ret[date('F Y', strtotime($begin_date))] = $this->getEarnings($user_id, $begin_date, $end_date);
        };

        return $ret;
    }

    private function getEarnings($user_id, $from, $to)
    {
        $sql = "
        SELECT 
            IFNULL(SUM(amount), 0) AS earnings
        FROM cm_commission_payouts ccp
        WHERE EXISTS (
                        SELECT 1 
                        FROM cm_commission_periods cmp 
                        WHERE ccp.commission_period_id = cmp.id 
                            AND cmp.end_date BETWEEN :from AND :to 
                            AND is_locked = 1
                        )
            AND ccp.payee_id = :user_id";
            
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":from", $from);
        $stmt->bindParam(":to", $to);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result;
	}

    public function getQualificationRequirementDetails($user_id)
    {
        $approveAccounts = $this->getLastThreeApprovedAccounts($user_id);
        
        $ret['qualified_text'] = 'No';
        $ret['qualified_requirement'] = 'You must have 3 PEA in the last 90 days';

        if(count($approveAccounts) >= 3) {
            if(array_key_exists(1, $approveAccounts)) {
                
                $qualified_date = $approveAccounts[1];
                $affiliated_date = $this->getAffiliatedDate($user_id);

                if(count($affiliated_date) > 0) {

                    $real_qualified_date = $affiliated_date['first_90_days_end_date'];

                    if(strtotime($qualified_date['qualified_until_date']) > strtotime($affiliated_date['first_90_days_end_date'])) {
                        $real_qualified_date = $qualified_date['qualified_until_date'];
                    }

                    $ret['qualified_text'] = 'Yes';
                    $ret['qualified_requirement'] = 'Yes until ' . date('d/m/Y', strtotime($real_qualified_date));

                } else {
                    $real_qualified_date = $qualified_date['qualified_until_date'];

                    $ret['qualified_text'] = 'Yes';
                    $ret['qualified_requirement'] = 'Yes until ' . date('d/m/Y', strtotime($real_qualified_date));
                }
            }  
        }
        elseif(count($approveAccounts) >= 1 && count($approveAccounts) <= 2) {
            $ret['qualified_requirement'] = 'You only have '.count($approveAccounts).'/3 PEA in the last 90 days';
        }
        
        return $ret;
    }

    public function getAffiliatedDate($user_id)
    {
        $sql = "
            SELECT
                DATE_ADD(a.affiliated_date, INTERVAL 90 DAY) first_90_days_end_date
            FROM cm_affiliates a
            WHERE a.user_id = $user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }
	
    public function getLastThreeApprovedAccounts($sponsor_id)
    {
        $sql = "
            SELECT ceal.id, DATE_ADD(ceal.created_at, INTERVAL 90 DAY) AS qualified_until_date
            FROM cm_energy_account_logs ceal
            JOIN cm_energy_accounts cea ON ceal.energy_account_id = cea.id
            WHERE cea.sponsor_id = :sponsor_id
            AND ceal.current_status IN (4) 
			AND ceal.`created_at` BETWEEN DATE_SUB(NOW(), INTERVAL 90 DAY) AND NOW()
            ORDER BY ceal.created_at DESC LIMIT 3
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':sponsor_id', $sponsor_id);
        $stmt->execute();
		
		$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

	public function getNextRankDeets(DailyVolume $volume, Rank $nextRank)
	{
		if ($nextRank === null || $volume === null) return [];

        $needs = [];

        //PERSONAL ENERGY ACCOUNTS
        $pea = $volume->pea ? $volume->pea : 0;

        //TEAM ACCOUNTS
        $ta = $volume->ta ? $volume->ta : 0;

        //QUALIFIED TOTAL ACCOUNTS
        $qta = $volume->qta ? $volume->qta : 0;

        //MAR
        $mar = $volume->mar ? $volume->mar : 0;

        /** 
         * Requirement 
         * **/

        $requiredPea = $nextRank->pea - $pea;
        $requiredTa = $nextRank->ta_requirement - $ta;
        $requiredQta = $nextRank->qta_requirement - $qta;

        if ($requiredPea > 0) {
            $needs[] = [
                'value' => $requiredPea,
                'description' => 'PEA',
                'label' => 'Personal Energy Accounts',
            ];
        }

        if ($requiredTa > 0) {
            $needs[] = [
                'value' => $requiredTa,
                'description' => 'TA',
                'label' => 'Team Accounts',
            ];
        }

        if ($requiredQta > 0) {
            $needs[] = [
                'value' => $requiredQta,
                'description' => 'QTA',
                'label' => 'Qualified Team Accounts',
            ];
		}
		
		if ($mar > 0) {
            $needs[] = [
                'value' => $mar,
                'description' => 'Max Account Rule',
                'label' => 'Max Account Rule',
            ];
		}

		//leg requirements
		if ($nextRank->id >= 6) {
			$legs = $this->getLegs($volume->user_id);

			$legRequirement = [];
			if ($nextRank->id == 6) {
				$legRequirement = $this->checkNextRankLegRequirements($legs, [4, 4]);
			} else if ($nextRank->id == 7) {
				$legRequirement = $this->checkNextRankLegRequirements($legs, [5, 5]);
			} else if ($nextRank->id == 8) {
				$legRequirement = $this->checkNextRankLegRequirements($legs, [6, 6]);
			} else if ($nextRank->id == 9) {
				$legRequirement = $this->checkNextRankLegRequirements($legs, [7, 7]);
			} else if ($nextRank->id == 10) {
				$legRequirement = $this->checkNextRankLegRequirements($legs, [8, 8]);
			} else if ($nextRank->id == 11) {
				$legRequirement = $this->checkNextRankLegRequirements($legs, [8, 9, 9]);
			} else if ($nextRank->id == 12) {
				$legRequirement = $this->checkNextRankLegRequirements($legs, [8, 8, 10, 10]);
			}

			$legRequirementText = '';
			$first = true;
			foreach ($legRequirement as $key => $value) {
				$rank = Rank::find($key);

				if ($first) {
					$legRequirementText .= $value . ' ' . $rank->description . ' or higher ';
					$first = false;
				} else {
					$legRequirementText .= ' and ' . $value . ' ' . $rank->description . ' or higher ';
				}
			}

			$needs[] = [
				'value' => $legRequirementText == '' ? 'N/A' : $legRequirementText,
				'description' => 'Leg Requirements',
				'label' => 'Leg Requirements',
			];
		}


	   return $needs;
	}
	
	public function getCurrentRankDeets(DailyVolume $volume, Rank $currentRank)
	{
        if ($currentRank === null || $volume === null) return [];

        $deets = [];

        //PERSONAL ENERGY ACCOUNTS
        $pea = $volume->pea ? $volume->pea : 0;

        //TEAM ACCOUNTS
        $ta = $volume->ta ? $volume->ta : 0;
        
        $ta -= $pea;

        //QUALIFIED TOTAL ACCOUNTS
        $qta = $volume->qta ? $volume->qta : 0;

        //MAR
        $mar = $volume->mar ? $volume->mar : 0;

		if ($currentRank->pea > 0 || $currentRank->id == 1) {
            $deets[] = [
                'value' => $pea,
                'description' => 'PEA',
                'label' => 'Personal Energy Accounts',
            ];
		 }
 
		 if ($currentRank->ta_requirement > 0) {
            $deets[] = [
                'value' => $ta,
                'description' => 'TA',
                'label' => 'Team Accounts',
            ];
		 }
 
		 if ($currentRank->qta_requirement > 0) {
            $deets[] = [
                'value' => $qta,
                'description' => 'QTA',
                'label' => 'Qualified Team Accounts',
            ];
		 }

		 if ($currentRank->mar_limit > 0) {
			 $deets[] = [
				 'value' => $currentRank->mar_limit,
				 'description' => 'Max Account Rule',
				 'label' => 'Max Account Rule',
			 ];
		  }

		 //leg requirements
		 if ($currentRank->id >= 6) {
			$legs = $this->getLegs($volume->user_id);

			$legRequirement = [];
			if ($currentRank->id == 6) {
				$legRequirement = $this->checkCurrentLeg($legs, [4, 4]);
			 } else if ($currentRank->id == 7) {
				$legRequirement = $this->checkCurrentLeg($legs, [5, 5]);
			 } else if ($currentRank->id == 8) {
				$legRequirement = $this->checkCurrentLeg($legs, [6, 6]);
			 } else if ($currentRank->id == 9) {
				$legRequirement = $this->checkCurrentLeg($legs, [7, 7]);
			} else if ($currentRank->id == 10) {
				$legRequirement = $this->checkCurrentLeg($legs, [8, 8]);
			} else if ($currentRank->id == 11) {
				$legRequirement = $this->checkCurrentLeg($legs, [8, 9, 9]);
			} else if ($currentRank->id == 12) {
				$legRequirement = $this->checkCurrentLeg($legs, [8, 8, 10, 10]);
			}

			$legRequirementText = '';
			$first = true;
			foreach ($legRequirement as $key => $value) {
				$rank = Rank::find($key);

				if ($first) {
					$legRequirementText .= $value . ' ' . $rank->description . ' or higher ';
					$first = false;
				} else {
					$legRequirementText .= ' and ' . $value . ' ' . $rank->description . ' or higher ';
				}
			}

			$deets[] = [
				'value' => $legRequirementText ,
				'description' => 'Leg Requirements',
				'label' => 'Leg Requirements',
			];
		 }

		 return $deets;
	}

	private function getLegs($sponsorid)
	{
		$sql = "
			SELECT 
				GROUP_CONCAT(u.id) AS legs
			FROM users u
			WHERE u.sponsorid = :sponsorid AND levelid = 3 
            AND EXISTS(SELECT 1 FROM categorymap WHERE userid = u.id AND catid = 14)
			GROUP BY u.sponsorid";

		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':sponsorid', $sponsorid);
		$stmt->execute();
	
		$legs = $stmt->fetch(\PDO::FETCH_ASSOC);

		return $legs;
	}

	private function checkCurrentLeg($legs, $targets = []) 	//$target = [1, 1, 3]
	{
		$affiliates = config('commission.affiliate');

		$targetsDup = $targets;
		$legs = explode(',', $legs['legs']);
		$legRanks = [];
        
		foreach ($legs as $leg) {
			$sql = "
				WITH RECURSIVE downline (root_id, user_id, parent_id, `level`) AS (
					SELECT 
						s.sponsorid AS root_id,
						s.id AS user_id,
						s.sponsorid AS parent_id,
						1 AS `level`
					FROM users s
					WHERE s.id = :rootid

					UNION

					SELECT
						d.root_id,
						s.id AS user_id,
						s.sponsorid AS parent_id,
						d.`level` + 1 `level`
					FROM users s
					INNER JOIN downline d ON d.user_id = s.sponsorid
					INNER JOIN categorymap c ON s.id = c.userid
					WHERE s.levelid = 3 AND c.catid IN ('$affiliates')
				)
				SELECT
					rank_id
				FROM downline d
				INNER JOIN cm_daily_ranks cdr ON d.user_id = cdr.user_id
				AND cdr.rank_date = :rank_date
				GROUP BY cdr.rank_id
			";

			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':rootid', $leg);
			$today = date('Y-m-d');
			$stmt->bindParam(':rank_date', $today);
			$stmt->execute();
		
			$legResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			

			foreach ($legResults as $legResult) {
				$legRanks[$leg][] = $legResult['rank_id'];
			}
		}

        if(count($legRanks) === 0 ) return [];

		uksort($legRanks, function($a, $b) { return count($b) - count($a); });

		$targetLocked = [];

		foreach ($legRanks as $key => $values) {
			foreach ($values as $legRank) {
				foreach ($targets as $targetKey => $targetValue) {
					if ($legRank >= $targetValue) {
						if (isset($targetLocked[$targetValue])) {
							$targetLocked[$targetValue] += 1;
						} else {
							$targetLocked[$targetValue] = 1;
						}
						unset($legRanks[$key]);
						unset($targets[$targetKey]);
					}
				}
			}
		}

		if (count($targetLocked) == 0) {
			foreach ($targetsDup as $dup) {
				if (!isset($targetLocked[$targetValue])) {
					$targetLocked[$targetValue] = 0;
				}
			}
		}

		return $targetLocked;
	}

	private function checkNextRankLegRequirements($legs, $targets = []) 	//$target = [1, 1, 3]
	{
		$affiliates = config('commission.affiliate');

		$legs = explode(',', $legs['legs']);
		$legRanks = [];
		foreach ($legs as $leg) {
			$sql = "
				WITH RECURSIVE downline (root_id, user_id, parent_id, `level`) AS (
					SELECT 
						s.sponsorid AS root_id,
						s.id AS user_id,
						s.sponsorid AS parent_id,
						1 AS `level`
					FROM users s
					WHERE s.id = :rootid

					UNION

					SELECT
						d.root_id,
						s.id AS user_id,
						s.sponsorid AS parent_id,
						d.`level` + 1 `level`
					FROM users s
					INNER JOIN downline d ON d.user_id = s.sponsorid
					INNER JOIN categorymap c ON s.id = c.userid
					WHERE s.levelid = 3 AND c.catid IN ('$affiliates')
				)
				SELECT
					rank_id
				FROM downline d
				INNER JOIN cm_daily_ranks cdr ON d.user_id = cdr.user_id
				AND cdr.rank_date = :rank_date
				GROUP BY cdr.rank_id
			";

			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':rootid', $leg);
			$today = date('Y-m-d');
			$stmt->bindParam(':rank_date', $today);
			$stmt->execute();
		
			$legResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
			foreach ($legResults as $legResult) {
				$legRanks[$leg][] = $legResult['rank_id'];
			}
		}

		uksort($legRanks, function($a, $b) { return count($b) - count($a); });

		$targetLocked = [];

		foreach ($legRanks as $key => $values) {
			foreach ($values as $legRank) {
				foreach ($targets as $targetKey => $targetValue) {
					if ($legRank >= $targetValue) {
						unset($legRanks[$key]);
						unset($targets[$targetKey]);
					}
				}
			}
		}

		foreach ($targets as $t) {
			if (isset($targetLocked[$t])) {
				$targetLocked[$t] += 1;
			} else {
				$targetLocked[$t] = 1;
			}
		}

		return $targetLocked;
	}


}