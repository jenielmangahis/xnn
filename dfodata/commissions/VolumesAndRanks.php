<?php


namespace Commissions;

use App\Rank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\DailyVolume;
use \PDO;
use DateTime;


final class VolumesAndRanks extends Console
{
    const MAX_POINTS = 200;
    const MIN_ACTIVE_POINTS = 100;

    protected $db;
    protected $end_date;
    protected $start_date;
    protected $affiliates;
    protected $customers;
    protected $root_user_id;
    protected $rank_requirements;

    public function __construct($end_date = null)
    {
        $this->db = DB::connection()->getPdo();
        $this->affiliates = config('commission.member-types.affiliates');
        $this->customers = config('commission.member-types.customers');
        $this->root_user_id = 3;

        $this->setDates($end_date);
    }

    private function process()
    {
        DB::transaction(function () {

            $this->setMainParameters();

            $this->log("Start Date: " . $this->getStartDate());
            $this->log("End Date: " . $this->getEndDate());

            $this->log("Customer IDs: " . $this->customers);
            $this->log("Affiliate IDs: " . $this->affiliates);

            $this->log("Max Points per user: " . static::MAX_POINTS);

            $influencer_1 = config('commission.ranks.influencer-1');
            $silver_influencer_1 = config('commission.ranks.silver-influencer-1');

            $this->log("Deleting ranks and volumes records of customers");
            $this->deleteCustomerRecords();

            $this->log("Deleting achieved ranks");
            $this->deleteAchievedRanks();

            $this->log("Getting rank requirements");
            $this->getRankRequirements();
            
            $this->log('Initializing Volumes');
            $this->initializeVolumes();

            $this->log('Initializing Ranks');
            $this->initializeRanks();

            $this->log('Setting PV');
            $this->setPv();

            $this->log('Setting L1V');
            $this->setL1V();   

            $this->log("Setting Minimum Rank");
            $this->setMinimumRank();

            $this->log("Setting Paid-as Rank");
            $this->setRanks();

            $this->log("Setting If Member Is Active");
            $this->setIfMemberIsActive();

            $this->log("Deleting Previous Highest Achieved Rank This Month");
            $this->deletePreviousHighestAchievedRanksThisMonth();

        }, 3);
    }

    private function setMainParameters()
    {
        $this->db->prepare("
            SET @root_user_id = :root_user_id,
                @start_date = :start_date,
                @end_date = :end_date,
                @affiliates = :affiliates,
                @customers = :customers,
                @max_points = :max_points,
                @min_active_points = :min_active_points
            ")
            ->execute([
                ':root_user_id' => $this->root_user_id,
                ':start_date' => $this->getStartDate(),
                ':end_date' => $this->getEndDate(),
                ':customers' => $this->customers,
                ':affiliates' => $this->affiliates,
                ':max_points' => static::MAX_POINTS,
                ':min_active_points' => static::MIN_ACTIVE_POINTS,
            ]);

        if (false) {
            $stmt = $this->db->prepare("
                SELECT
                    @root_user_id,
                    @start_date,
                    @end_date,
                    @affiliates,
                    @customers,
                    @max_points,
                    @min_active_points
            ");

            $stmt->execute();

            $this->log_debug($stmt->fetch());
        }
    }

    private function setIfMemberIsActive()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            JOIN users u ON u.id = dv.user_id
            SET 
                dr.is_active =  dr.paid_as_rank_id > 1, -- the same as checking the preferred customer. minimum rank is considered.
                dr.is_system_active = (u.active = 'Yes')
            WHERE dv.volume_date = @end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setRanks()
    {
        $volumes = DailyVolume::date($this->getEndDate())->orderBy('level', 'desc')->orderBy('user_id', 'desc')->get();

        $level = null;

        foreach ($volumes as $volume) {

            if ($level !== $volume->level) {
                $level = $volume->level;
                $this->log("Processing Level $level");
            }

            $user_id = +$volume->user_id;
            $rank    = $volume->rank;

            $rank->rank_id = $this->getRank($volume);
            $rank->paid_as_rank_id = $rank->rank_id > $rank->min_rank_id ? $rank->rank_id : $rank->min_rank_id;

            $rank->save();

            $this->saveAchievedRank($volume->user_id, $rank->paid_as_rank_id);
        }
    }

    private function getReferralPointsFromRankAdvancement($member_id)
    {
        $sql = "
            SELECT
                SUM(IF(dr.paid_as_rank_id >= a.rank_id, 6, 0)) points,
                CONCAT('[', 
                    GROUP_CONCAT(JSON_OBJECT(
                        'user_id', a.user_id,
                        'is_rank_maintained', IF(dr.paid_as_rank_id >= a.rank_id, 1, 0),
                        'maintained_rank_id', dr.paid_as_rank_id,
                        'achieved_rank_id', a.rank_id,
                        'achieved_date', a.date_achieved,
                        'third_month_date', LAST_DAY(DATE_ADD(a.date_achieved, INTERVAL 2 MONTH)),
                        'points', IF(dr.paid_as_rank_id >= a.rank_id, 6, 0)
                    )), 
                ']') `users`
            FROM cm_achieved_ranks a
            JOIN users u ON u.id = a.user_id
            JOIN cm_daily_ranks dr ON dr.user_id = a.user_id AND dr.rank_date = @end_date
            WHERE u.sponsorid = :member_id
                AND @end_date BETWEEN a.date_achieved  AND LAST_DAY(DATE_ADD(a.date_achieved, INTERVAL 2 MONTH))
                AND a.rank_id BETWEEN :influencer_1 AND :silver_influencer_1
        ";

        $influencer_1 = config('commission.ranks.influencer-1');
        $silver_influencer_1 = config('commission.ranks.silver-influencer-1');

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':member_id', $member_id);
        $stmt->bindParam(':influencer_1', $influencer_1);
        $stmt->bindParam(':silver_influencer_1', $silver_influencer_1);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getEnrolledCoachesRankCount($member_id)
    {
        $sql = "
            SELECT
                r.`group`,
                COUNT(a.rank_id) `count`
            FROM cm_ranks r
            LEFT JOIN (
                SELECT
                    dr.paid_as_rank_id AS rank_id
                FROM cm_daily_ranks dr 
                JOIN users u ON u.id = dr.user_id
                WHERE dr.rank_date = @end_date AND u.sponsorid = :member_id
            ) a ON a.rank_id = r.id
            GROUP BY r.`group`
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':member_id', $member_id);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return collect($result)->mapWithKeys(function ($item) {
            return [$item['group'] => $item['count']];
        });
    }

    private function saveAchievedRank($user_id, $rank_id)
    {
        $sql = "
            INSERT INTO cm_achieved_ranks (user_id, rank_id, date_achieved) 
            SELECT 
                :user_id,
                r.id,
                @end_date
            FROM cm_ranks r
            WHERE r.id <= :rank_id
            ON DUPLICATE KEY UPDATE
                date_achieved = IF(date_achieved < VALUES(date_achieved), date_achieved, VALUES(date_achieved))

        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':rank_id', $rank_id);
        $stmt->execute();
    }

    private function getRank(DailyVolume $volume)
    {
        foreach ($this->rank_requirements as $rank) {

            $pv_count = +$volume->pv;
            $l1v_count = +$volume->l1v;

            if (
                $pv_count >= +$rank->pv
                && $l1v_count >= +$rank->l1v
            ) {
                return +$rank->id;
            }

        }

        return 1;
    }

    private function setMinimumRank()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN cm_minimum_ranks mr ON mr.user_id = dr.user_id
            SET dr.min_rank_id = mr.rank_id
            WHERE mr.is_deleted = 0 AND dr.rank_date = @end_date AND  @end_date BETWEEN mr.start_date AND mr.end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setPaidAsRank()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            SET dr.paid_as_rank_id = IF(dr.min_rank_id > dr.rank_id, dr.min_rank_id, dr.rank_id)
            WHERE dr.rank_date = @end_date
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();
    }

    private function setAchievedRank()
    {
        $sql = "
            INSERT INTO cm_achieved_ranks (user_id, rank_id, date_achieved) 
            SELECT
                dr.user_id,
                dr.paid_as_rank_id,
                dr.rank_date
            FROM cm_daily_ranks dr
            WHERE dr.rank_date = @end_date
            ON DUPLICATE KEY UPDATE
                date_achieved = IF(date_achieved < VALUES(date_achieved), date_achieved, VALUES(date_achieved))

        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function deleteCustomerRecords()
    {
        $sql = "
            DELETE dr FROM cm_daily_ranks dr 
            WHERE dr.rank_date = @end_date
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = dr.user_id AND FIND_IN_SET(cm.catid, @customers))
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $sql = "
            DELETE dv FROM cm_daily_volumes dv
            WHERE dv.volume_date = @end_date
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = dv.user_id AND FIND_IN_SET(cm.catid, @customers))
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->rowCount();

        $this->log("{$rows} row(s) deleted");
    }

    private function deleteAchievedRanks()
    {
        $sql = "
            DELETE a FROM cm_achieved_ranks a
            WHERE a.date_achieved = @end_date 
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->rowCount();

        $this->log("{$rows} row(s) deleted");
    }

    private function deletePreviousHighestAchievedRanksThisMonth()
    {
        $sql = "
            DELETE a FROM cm_achieved_ranks AS a
            JOIN cm_daily_ranks dr ON dr.user_id = a.user_id  AND dr.rank_date = @end_date
            WHERE a.date_achieved BETWEEN @start_date AND @end_date
                AND a.rank_id > dr.paid_as_rank_id
                AND dr.rank_date > a.date_achieved
            ;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->rowCount();

        $this->log("{$rows} row(s) deleted");
    }

    private function getRankRequirements()
    {
        $this->rank_requirements = Rank::orderBy("id", "desc")->get();
    }

    protected function setDates($end_date = null)
    {
        $end_date = $this->getRealCarbonDateParameter($end_date);

        $this->end_date = $end_date->format("Y-m-d");
        $this->start_date = $end_date->copy()->firstOfMonth()->format("Y-m-d");
    }

    public function run($end_date = null)
    {
        $this->setDates($end_date);

        $this->process();
    }

    public function getEndDate()
    {
        if (!isset($this->end_date)) {
            throw new Exception("End date is not set.");
        }

        return $this->end_date;
    }

    public function setEndDate($end_date)
    {
        $this->throwIfInvalidDateFormat($end_date);
        $this->end_date = $end_date;
    }

    public function getStartDate()
    {
        if (!isset($this->start_date)) {
            throw new Exception("Start date is not set.");
        }
        return $this->start_date;
    }

    public function setStartDate($start_date)
    {
        $this->throwIfInvalidDateFormat($start_date);
        $this->start_date = $start_date;
    }

    public static function getNextRankRequirementsByDailyVolume(DailyVolume $volume, Rank $next_rank)
    {
        if ($next_rank === null || $volume === null) return [];

        $needs = [];

        $diamond_influencer_count = +$volume->diamond_influencer_count;
        $platinum_influencer_count = $diamond_influencer_count + +$volume->platinum_influencer_count;
        $gold_influencer_count = $platinum_influencer_count + +$volume->gold_influencer_count;
        $silver_influencer_count = $gold_influencer_count + +$volume->silver_influencer_count;
        $influencer_count = $silver_influencer_count + +$volume->influencer_count;

        $preferred_customer_count_requirement = $next_rank->preferred_customer_count_requirement - $volume->preferred_customer_count;
        $referral_points_requirement = $next_rank->referral_points_requirement - $volume->referral_points;
        $organization_points_requirement = $next_rank->organization_points_requirement - $volume->organization_points;
        $team_group_points_requirement = $next_rank->team_group_points_requirement - $volume->team_group_points;
        $gold_influencer_count_requirement = $next_rank->gold_influencer_count_requirement - $gold_influencer_count;

        if ($silver_influencer_count - $next_rank->gold_influencer_count_requirement < 0) {
            $silver_influencer_count_requirement = $next_rank->silver_influencer_count_requirement;
        } else {
            $silver_influencer_count_requirement = $next_rank->silver_influencer_count_requirement - ($silver_influencer_count - $next_rank->gold_influencer_count_requirement);
        }

        if ($influencer_count - $next_rank->gold_influencer_count_requirement - $next_rank->silver_influencer_count_requirement < 0) {
            $influencer_count_requirement = $next_rank->influencer_count_requirement;
        } else {
            $influencer_count_requirement = $next_rank->influencer_count_requirement - ($influencer_count - $next_rank->gold_influencer_count_requirement - $next_rank->silver_influencer_count_requirement);
        }

        if ($preferred_customer_count_requirement > 0) {
            $needs[] = [
                'value' => $preferred_customer_count_requirement,
                'description' => 'Preferred Customer(s)',
            ];
        }

        if ($referral_points_requirement > 0) {
            $needs[] = [
                'value' => $referral_points_requirement,
                'description' => 'Referral Points'
            ];
        }

        if ($organization_points_requirement > 0) {
            $needs[] = [
                'value' => $organization_points_requirement,
                'description' => 'Organization Points'
            ];
        }

        if ($team_group_points_requirement > 0) {
            $needs[] = [
                'value' => $team_group_points_requirement,
                'description' => 'Team Group Points'
            ];
        }

        if ($gold_influencer_count_requirement > 0) {
            $needs[] = [
                'value' => $gold_influencer_count_requirement,
                'description' => 'Gold Influencer(s)'
            ];
        }

        if ($silver_influencer_count_requirement > 0) {
            $needs[] = [
                'value' => $silver_influencer_count_requirement,
                'description' => 'Silver Influencer(s)'
            ];
        }

        if ($influencer_count_requirement > 0) {
            $needs[] = [
                'value' => $influencer_count_requirement,
                'description' => 'Influencer(s)'
            ];
        }

        if (false && "test") {
            $needs[] = [
                'html' => '<h1>HTML TEST</h1>',
            ];
        }

        return $needs;
    }

    private function initializeVolumes()
    {
        $sql = "
            INSERT INTO cm_daily_volumes (
                user_id, 
                volume_date, 
                pv,
                l1v
            )
            WITH RECURSIVE downline (user_id, parent_id,`active`) AS (
                SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    active
                FROM users u
                WHERE u.id = @root_user_id AND u.levelid = 3
                
                UNION ALL
                
                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    p.active
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                WHERE p.levelid = 3
                
            )
            SELECT
                d.user_id, 
                @end_date volume_date, 
                0 pv,
                0 l1v
            FROM downline d
            ON DUPLICATE KEY UPDATE
                pv = 0,
                l1v = 0,
                dt_created = CURRENT_TIMESTAMP(),
                dt_updated = CURRENT_TIMESTAMP()
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function initializeRanks()
    {
        $sql = "
            INSERT INTO cm_daily_ranks (
                user_id, 
                volume_id, 
                rank_date, 
                rank_id, 
                min_rank_id, 
                paid_as_rank_id,
                is_active,
                is_system_active
            )
            SELECT 
                dv.user_id, 
                dv.id AS volume_id, 
                dv.volume_date AS rank_date, 
                1 AS rank_id, 
                1 AS min_rank_id, 
                1 AS paid_as_rank, 
                0 AS is_active,
                0 AS is_system_active
            FROM cm_daily_volumes dv
            WHERE volume_date = @end_date
            ON DUPLICATE KEY UPDATE 
                min_rank_id = 1,
                rank_id = 1,
                paid_as_rank_id = 1,
                is_active = 0,
                is_system_active = 0,
                volume_id = VALUES(volume_id),
                dt_created = CURRENT_TIMESTAMP(),
                dt_updated = CURRENT_TIMESTAMP();
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setPv()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    t.user_id,
                    SUM(COALESCE(t.computed_cv, 0)) As ps
                FROM v_cm_transactions t
                WHERE transaction_date BETWEEN @start_date AND @end_date
                    AND t.`type` = 'product'                    
                GROUP BY t.user_id
            ) AS a ON a.user_id = dv.user_id             
            SET
                dv.pv = COALESCE(a.ps, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setL1V()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`, pv) AS (
                    SELECT 
                        p.user_id,
                        p.sponsor_id AS parent_id,
                        p.user_id AS root_id,
                        0 AS `level`,
                        dv.pv AS pv
                    FROM cm_genealogy_placement p
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
                    JOIN users u ON dv.user_id = u.id 
                    WHERE u.levelid = 1
                    
                    UNION ALL
                    
                    SELECT
                        p.user_id AS user_id,
                        p.sponsor_id AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`,
                        dv.pv AS pv
                    FROM cm_genealogy_placement p
                    JOIN downline ON downline.user_id = p.sponsor_id                    
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
                    JOIN users uu ON dv.user_id = uu.id
                    WHERE uu.levelid = 1
                )
                SELECT 
                    d.root_id AS user_id,
                    SUM(d.pv) AS total_pv
                FROM downline d
                WHERE d.root_id <> d.user_id
                GROUP BY d.root_id
            ) AS a ON a.user_id = dv.user_id             
            SET
                dv.l1v = COALESCE(a.total_pv, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        //return $smt->fetchColumn();
    }

}