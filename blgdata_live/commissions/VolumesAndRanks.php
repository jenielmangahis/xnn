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
                    t.sponsor_id AS user_id,
                    SUM(COALESCE(t.sub_total, 0)) AS total_sales
                FROM v_cm_transactions t
                WHERE t.`type` = 'product'
				AND FIND_IN_SET(t.purchaser_catid, @customers)
                GROUP BY t.sponsor_id
            ) AS a ON a.user_id = dv.user_id    -- GET LIFETIME CUSTOMER SALES
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
                        IF(a.total_sales > 0, (
                            IF(a.total_sales >= 25000 AND a.total_sales < 250000, 2, 3)
                        ), 1)
                    )
				),
				dv.total_sales = COALESCE(a.total_sales, 0)
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
            $bg10_count = +$volume->bg10_count;    
            $bg9_count = $bg10_count + +$volume->bg9_count;
            $bg8_count = $bg9_count + +$volume->bg8_count;    
            $bg7_count = $bg8_count + +$volume->bg7_count;    
            $bg6_count = $bg7_count + +$volume->bg6_count;    
            $bg5_count = $bg6_count + +$volume->bg5_count;    
                    
            if (
                +$volume->pv >= +$rank->pv_requirement
                && +$volume->gv >= +$rank->gv_requirement
                && +$bg5_count >= +$rank->bg5_requirement
                && +$bg6_count >= +$rank->bg6_requirement
                && +$bg7_count >= +$rank->bg7_requirement
                && +$bg8_count >= +$rank->bg8_requirement
                && +$bg9_count >= +$rank->bg9_requirement
                && +$bg10_count >= +$rank->bg10_requirement
            ) return +$rank->id;

        }

        return config('commission.ranks.ambassador');
    }

    private function setBgRanks()
    {
        $volumes = DailyVolume::with('rank')->date($this->getEndDate())
            ->orderBy('level', 'desc')->orderBy('user_id', 'desc')->get();

        $level = null;

        foreach ($volumes as $volume) {

            if ($level !== $volume->level) {
                $level = $volume->level;
                $this->log("Processing Level $level");
            }

            $user_id = +$volume->user_id;
            $rank = $volume->rank;

            $rank->rank_id = $this->getBgRank($volume);
            $rank->paid_as_rank_id = $rank->rank_id > +$rank->min_rank_id ? $rank->rank_id : $rank->min_rank_id;
            
            $this->saveAchievedRank($user_id, $rank->paid_as_rank_id);

            $rank->save();
        }
    }

    private function setPv()
    {
        // $sql = "
        //     UPDATE cm_daily_volumes dv
        //     LEFT JOIN (
        //         WITH RECURSIVE downline (user_id, parent_id, root_id, `level`, `active`, `compress_level`) AS (
        //             SELECT 
        //                 p.id AS user_id,
        //                 p.sponsorid AS parent_id,
        //                 p.id AS root_id,
        //                 0 AS `level`,
        //                 active,
        //                 0 AS `compress_level`
        //             FROM users p
        //             WHERE p.id = @root_user_id

        //             UNION ALL 

        //             SELECT
        //                 p.id AS user_id,
        //                 p.sponsorid AS parent_id,
        //                 downline.root_id,
        //                 downline.`level` + 1 `level`,
        //                 p.active,
        //                 downline.compress_level + IF(p.active = 'Yes', 1, 0)
        //             FROM users p
        //             JOIN downline ON downline.user_id = p.sponsorid
		// 			WHERE downline.compress_level < 1
        //         )
        //         SELECT
        //             t.user_id,
        //             SUM(COALESCE(t.computed_cv, 0)) As pv
        //         FROM downline d
        //         JOIN v_cm_transactions t ON t.user_id = d.user_id
        //         WHERE transaction_date BETWEEN @start_date AND @end_date
        //             AND t.`type` = 'product'
        //             AND FIND_IN_SET(t.purchaser_catid, @customers,  )
        //             AND d.level <= 2
        //     ) AS a ON a.user_id = dv.user_id             
        //     SET
        //         dv.pv = COALESCE(a.pv, 0)
        //     WHERE dv.volume_date = @end_date
        // ";

        $customers = config('commission.member-types.customers');
        $influencer = config('commission.member-types.influencer');
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    u.sponsorid,
                    SUM(COALESCE(t.computed_cv, 0)) AS pv
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
                WHERE t.transaction_date BETWEEN @start_date AND @end_date
                AND t.type = 'product'
                AND FIND_IN_SET(t.purchaser_catid, '$customers,$influencer')
                GROUP BY u.sponsorid
            ) AS a ON dv.user_id = a.sponsorid
            SET dv.pv = COALESCE(a.pv, 0)
            WHERE dv.volume_date = @end_date";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setGv()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`, pv, active, `compress_level`) AS (
                    SELECT 
                        p.user_id,
                        p.sponsor_id AS parent_id,
                        p.user_id AS root_id,
                        0 AS `level`,
                        u.active,
                        dv.pv AS pv,
                        0 AS `compress_level`
                    FROM cm_genealogy_placement p
                    JOIN users u ON u.id = p.user_id
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
                    
                    UNION ALL
                    
                    SELECT
                        p.user_id AS user_id,
                        p.sponsor_id AS parent_id,
                        u.active,
                        downline.root_id,
                        downline.`level` + 1 `level`,
                        dv.pv AS pv,
                        downline.compress_level + IF(u.active = 'Yes', 1, 0)
                    FROM cm_genealogy_placement p
                    JOIN users u ON u.id = p.user_id
                    JOIN downline ON downline.user_id = p.sponsor_id
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
					WHERE downline.compress_level < 1
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
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`) AS (
                    SELECT 
                        p.user_id,
                        p.sponsor_id AS parent_id,
                        p.user_id AS root_id,
                        0 AS `level`
                    FROM cm_genealogy_placement p
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
                    
                    UNION ALL
                    
                    SELECT
                        p.user_id AS user_id,
                        p.sponsor_id AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`
                    FROM cm_genealogy_placement p
                    JOIN downline ON downline.user_id = p.sponsor_id
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
                )
                SELECT 
                    d.root_id AS user_id,
                    COUNT(d.user_id) AS bg_count
                FROM downline d
                JOIN cm_daily_ranks cdr ON d.user_id = cdr.user_id
                WHERE d.root_id <> d.user_id AND cdr.rank_date = @end_date AND cdr.paid_as_rank_id = $bgLevel
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
                @customers = :customers
            ")
            ->execute([
                ':root_user_id' => $this->root_user_id,
                ':start_date' => $this->getStartDate(),
                ':end_date' => $this->getEndDate(),
                ':customers' => $this->customers,
                ':affiliates' => $this->affiliates
            ]);

        if (false) {
            $stmt = $this->db->prepare("
                SELECT
                    @root_user_id,
                    @start_date,
                    @end_date,
                    @affiliates,
                    @customers
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
            WITH RECURSIVE downline (user_id, parent_id, `level`,`active`) AS (
                SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    0 AS `level`,
                    active
                FROM users u
                WHERE u.id = @root_user_id AND u.levelid = 3
                
                UNION ALL
                
                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    downline.`level` + 1 `level`,
                    p.active
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                WHERE p.levelid = 3
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

        $bg10_count = +$volume->bg10_count;    
        $bg9_count = $bg10_count + +$volume->bg9_count;
        $bg8_count = $bg9_count + +$volume->bg8_count;    
        $bg7_count = $bg8_count + +$volume->bg7_count;    
        $bg6_count = $bg7_count + +$volume->bg6_count;    
        $bg5_count = $bg6_count + +$volume->bg5_count;  

        $pv_needs = $next_rank->pv_requirement - $volume->pv;
        $gv_needs = $next_rank->gv_requirement - $volume->gv;
        
         
        if($next_rank->bg10_requirement == 0){ $bg10_needs_count = 0; }else{ $bg10_needs_count = $next_rank->bg10_requirement - $bg10_count; } 
        if($next_rank->bg9_requirement == 0){ $bg9_needs_count = 0; }else{ $bg9_needs_count = $next_rank->bg9_requirement - $bg9_count; }
        if($next_rank->bg8_requirement == 0){ $bg8_needs_count = 0; }else{ $bg8_needs_count = $next_rank->bg8_requirement - $bg8_count; }
        if($next_rank->bg7_requirement == 0){ $bg7_needs_count = 0; }else{ $bg7_needs_count = $next_rank->bg7_requirement - $bg7_count; }
        if($next_rank->bg6_requirement == 0){ $bg6_needs_count = 0; }else{ $bg6_needs_count = $next_rank->bg6_requirement - $bg6_count; }
        if($next_rank->bg5_requirement == 0){ $bg5_needs_count = 0; }else{ $bg5_needs_count = $next_rank->bg5_requirement - $bg5_count; }

            
        if($pv_needs > 0) {
            $needs[] = [
                'value' => $pv_needs,
                'description' => 'PV',
            ];
        }

        if($gv_needs > 0) {
            $needs[] = [
                'value' => $gv_needs,
                'description' => 'GV',
            ];
        }

        if($bg5_needs_count > 0) {
            $needs[] = [
                'value' => $bg5_needs_count,
                'description' => 'Pearl Influencer or Higher',
            ];
        }

        if($bg6_needs_count > 0) {
            $needs[] = [
                'value' => $bg6_needs_count,
                'description' => 'Emerald Influencer or Higher',
            ];
        }

        if($bg7_needs_count > 0) {
            $needs[] = [
                'value' => $bg7_needs_count,
                'description' => 'Ruby Influencer or Higher',
            ];
        }

        if($bg8_needs_count > 0) {
            $needs[] = [
                'value' => $bg8_needs_count,
                'description' => 'Diamond Influencer or Higher',
            ];
        }

        if($bg9_needs_count > 0) {
            $needs[] = [
                'value' => $bg9_needs_count,
                'description' => 'Double Diamond Influencer or Higher',
            ];
        }

        if($bg10_needs_count > 0) {
            $needs[] = [
                'value' => $bg10_needs_count,
                'description' => 'Triple Diamond Influencer or Higher',
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