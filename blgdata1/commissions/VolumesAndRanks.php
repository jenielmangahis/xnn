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
    const MIN_ACTIVE_POINTS = 40;
    const MAX_LEG_RULE = 0.5;

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
            $this->initializeBlgVolumes();

            $this->log('Initializing Ranks');
            $this->initializeBlgRanks();

            $this->log('Setting PV');
            $this->setPv();

            $this->log('Setting GV');
            $this->setGv();

            $this->log('Setting BG Level');
            $this->setBg();

            $this->log("Setting Bg Minimum Rank");
            $this->setBgMinimumRank();

            $this->log("Setting Bg Paid-as Rank");
            $this->setBgRanks();

            $this->log("Setting Influencer Level");
            $this->setInfluencerLevel();

            $this->log("Setting If Member Is Active");
            $this->setIfMemberIsActive();

            $this->log("Deleting Previous Highest Achieved Rank This Month");
            $this->deletePreviousHighestAchievedRanksThisMonth();

        }, 3);
    }

    private function setInfluencerLevel() {
        $sql = "
            UPDATE cm_daily_ranks dv
            LEFT JOIN (
                SELECT
                    t.user_id,
                    SUM(COALESCE(t.computed_cv, 0)) As pv
                FROM v_cm_transactions t
                WHERE t.`type` = 'product'  -- DATE(transaction_date) BETWEEN @start_date AND @end_date
                                            -- AND t.`type` = 'product'
                GROUP BY t.user_id
            ) AS a ON a.user_id = dv.user_id    -- GET LIFETIME SALES
            LEFT JOIN (
                SELECT
                    user_id,
                    influencer_level
                FROM cm_minimum_ranks
                WHERE @end_date BETWEEN start_date AND end_date
            ) AS ab ON a.user_id = dv.user_id   -- GET MINIMUM RANK TOOL SETTINGS
            SET
                dv.influencer_level = IF(ab.influencer_level is not null, ab.influencer_level,
                    (
                        IF(a.pv > 0, (
                            IF(a.pv >= 25000 AND a.pv < 250000, 2, 3)
                        ), 1)
                    )
                )
            WHERE  dv.rank_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setBgMinimumRank()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN cm_minimum_ranks mr ON mr.user_id = dr.user_id
            SET dr.min_rank_id = mr.rank_id
            WHERE mr.is_deleted = 0 AND dr.rank_date = @end_date AND @end_date BETWEEN mr.start_date AND mr.end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function getBgRank(DailyVolume $volume)
    {
        foreach ($this->rank_requirements as $rank) {


            $bg5_count = +$volume->bg5_count;
            $bg6_count = $bg5_count + +$volume->bg6_count;
            $bg7_count = $bg6_count + +$volume->bg7_count;
            $bg8_count = $bg7_count + +$volume->bg8_count;
            $bg9_count = $bg8_count + +$volume->bg9_count;
            $bg10_count = $bg9_count + +$volume->bg10_count;
          

            $bg5_count_requirement = +$volume->bg5_count_requirement;
            $bg6_count_requirement = $bg5_count_requirement - +$volume->bg6_count_requirement;
            $bg7_count_requirement = $bg6_count_requirement - +$volume->bg7_count_requirement;
            $bg8_count_requirement = $bg7_count_requirement - +$volume->bg8_count_requirement;
            $bg9_count_requirement = $bg8_count_requirement - +$volume->bg9_count_requirement;
            $bg10_count_requirement = $bg9_count_requirement - +$volume->bg10_count_requirement;
           
                    
            if (
                +$volume->pv >= +$rank->pv_requirement
                && +$volume->gv >= +$rank->gv_requirement
                && +$volume->bg5_count >= +$rank->bg5_requirement
                && +$volume->bg6_count >= +$rank->bg6_requirement
                && +$volume->bg7_count >= +$rank->bg7_requirement
                && +$volume->bg8_count >= +$rank->bg8_requirement
                && +$volume->bg9_count >= +$rank->bg9_requirement
                && +$volume->bg10_count >= +$rank->bg10_requirement
            ) return +$rank->id;

        }

        return config('commission.ranks.team-member');
    }

    private function setBgRanks()
    {
        $volumes = DailyVolume::with('rank')->date($this->getEndDate())
            //->whereRaw("NOT EXISTS(SELECT 1 FROM cm_daily_ranks dr WHERE dr.volume_id = cm_daily_volumes.id AND FIND_IN_SET(dr.cat_id, '{$this->customers}'))")
            ->orderBy('level', 'desc')->orderBy('user_id', 'desc')->get();

        $level = null;

        foreach ($volumes as $volume) {

            if ($level !== $volume->level) {
                $level = $volume->level;
                $this->log("Processing Level $level");
            }

            $user_id = +$volume->user_id;
            $rank = $volume->rank;

            //check if customer
           /* if (+$rank->cat_id === 13) {
                $rank->rank_id = 1;
                $rank->paid_as_rank_id = 1;
            } else { */

                $rank->rank_id = $this->getBgRank($volume);
                $rank->paid_as_rank_id = $rank->rank_id > +$rank->min_rank_id ? $rank->rank_id : $rank->min_rank_id;

           // }

            //$rank->rank_id = $rank->paid_as_rank_id; // added by jen
            // if($rank->paid_as_rank_id > 2) {
            //     $rank->is_active = 1;
            // }

            
            /*
            if(+$volume->pv >= static::MIN_ACTIVE_PV) {
                $rank->is_active = 1;
            }
            elseif(+$rank->is_active === 0) {
                $rank->is_active = $this->getNextDelivery($user_id);
            }
            */
            
            $this->saveAchievedRank($user_id, $rank->paid_as_rank_id);

            $rank->save();
        }
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
                WHERE (transaction_date BETWEEN @start_date AND @end_date
                    AND t.`type` = 'product')
                    OR (
                    transaction_date BETWEEN @start_date AND @end_date
                    AND t.sponsor_id = dv.user_id
                )
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

    private function setGv()
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
                )
                SELECT 
                    d.root_id AS user_id,
                    SUM(d.pv) AS gv
                FROM downline d
                WHERE d.root_id <> d.user_id
                GROUP BY d.root_id
            ) AS a ON a.user_id = dv.user_id             
            SET
                dv.gv = COALESCE(a.gv, 0) + dv.pv
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        //return $smt->fetchColumn();
    }

    private function setBg()
    {
        $minBgLevel = 6;
        $maxBgLevel = 10;

        while($minBgLevel <= $maxBgLevel){
            $this->setBgCount($minBgLevel);
            $minBgLevel++;
        }
    }

    private function setBgCount($bgLevel)
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
                )
                SELECT 
                    d.root_id AS user_id,
                    COUNT(d.user_id) AS bg_count
                FROM downline d
                JOIN cm_daily_ranks cdr ON d.user_id = cdr.user_id
                WHERE d.root_id <> d.user_id AND cdr.rank_date = @end_date AND cdr.paid_as_rank_id >= $bgLevel
                GROUP BY d.root_id
            ) AS a ON a.user_id = dv.user_id  
            SET  dv.bg".$bgLevel."_count = a.bg_count
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
        
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
        
    private function initializeBlgVolumes()
    {
        $sql = "
                INSERT INTO cm_daily_volumes (
                user_id, 
                volume_date, 
                pv,
                gv,
                bg5_count,
                bg6_count,
                bg7_count,
                bg8_count,
                bg9_count,
                bg10_count,
                bg11_count,
                bg12_count,
                level
             
            )

            WITH RECURSIVE downline (user_id, parent_id, `level`,`active`, `compress_level`) AS (
                SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    0 AS `level`,
                    active,
                    0 AS `compress_level`
                FROM users u
                WHERE u.id = @root_user_id AND u.levelid = 3
                
                UNION ALL
                
                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    downline.`level` + 1 `level`,
                    p.active,
                    downline.compress_level + IF(p.active = 'Yes', 1, 0)
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                WHERE p.levelid = 3
                AND downline.`level` >= 1
            )
            SELECT
                d.user_id, 
                @end_date volume_date, 
                0 pv,
                0 gv,
                0 bg5_count,
                0 bg6_count,
                0 bg7_count,
                0 bg8_count,
                0 bg9_count,
                0 bg10_count,
                0 bg11_count,
                0 bg12_count,
                d.level
            FROM downline d
            ON DUPLICATE KEY UPDATE
                pv = 0,
                gv = 0,
                bg5_count = 0,
                bg6_count = 0,
                bg7_count = 0,
                bg8_count = 0,
                bg9_count = 0,
                bg10_count = 0,
                bg11_count = 0,
                bg12_count = 0,
                level = d.level,
                created_at = CURRENT_TIMESTAMP(),
                updated_at = CURRENT_TIMESTAMP()
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function initializeBlgRanks()
    {
        $sql = "
            INSERT INTO cm_daily_ranks (
                user_id, 
                volume_id, 
                rank_date, 
                rank_id, 
                min_rank_id, 
                paid_as_rank_id,
                influencer_level,
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
                1 influencer_level,
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
                influencer_level = 1,
                volume_id = VALUES(volume_id),
                updated_at = CURRENT_TIMESTAMP();
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

}