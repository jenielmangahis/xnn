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

            // $this->log("Influencer 1: ($influencer_1) Silver Influencer 1: ($silver_influencer_1)");

            $this->log("Deleting ranks and volumes records of customers");
            $this->deleteCustomerRecords();

            $this->log("Deleting achieved ranks");
            $this->deleteAchievedRanks();

            $this->log("Getting rank requirements");
            $this->getRankRequirements();

            /*$this->log('Initializing Volumes');
            $this->initializeVolumes();*/

            $this->log('Initializing Volumes');
            $this->initializeBlgVolumes();

            /*$this->log('Initializing Ranks');
            $this->initializeRanks();*/

            $this->log('Initializing Ranks');
            $this->initializeBlgRanks();

            //Calculate Volume and Ranks
            $this->log('Setting PV');
            $this->setPv();

            $this->log('Setting GV');
            $this->setGv();

            //$this->log('Setting BG Level');
            //$this->setBg();

            $this->log("Setting Bg Minimum Rank");
            $this->setBgMinimumRank();

            $this->log("Setting Bg Paid-as Rank");
            $this->setBgRanks();

            $this->log("Setting Influencer Level");
            $this->setInfluencerLevel();

            $this->log("Setting Coach Points");
            $this->setCoachPoints();

            $this->log("Setting Organization Points");
            $this->setOrganizationPoints();

            $this->log("Setting Team Group Points");
            $this->setTeamGroupPoints();

            $this->log("Setting Preferred Customer Count");
            $this->setPreferredCustomerCount();

            $this->log("Setting Referral Points from New Preferred Customers (3 months)");
            $this->setReferralPointsFromNewPreferredCustomers();

            $this->log("Setting Referral Points from New Active Coaches (3 months)");
            $this->setReferralPointsFromNewActiveEnrolledCoaches();

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

    private function setInfluencerLevel() {
        $sql = "
            UPDATE cm_daily_ranks dv
            LEFT JOIN (
                SELECT
                    t.user_id,
                    SUM(COALESCE(t.computed_cv, 0)) As pv
                FROM v_cm_transactions t
                WHERE DATE(transaction_date) BETWEEN @start_date AND @end_date
                 --   AND t.`type` = 'product'
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
            WHERE dv.rank_date = @end_date
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

            if (
                +$volume->rank->is_active
                && +$volume->pv >= +$rank->pv
                && +$volume->gv >= +$rank->gv
                && (+$volume->total_group_volume_left_leg * static::MAX_LEG_RULE) >= +$rank->binary_volume_requirement
                && (+$volume->total_group_volume_right_leg * static::MAX_LEG_RULE) >= +$rank->binary_volume_requirement
                && +$volume->bg5 >= +$rank->bg5
                && +$volume->bg6 >= +$rank->bg6
            ) return +$rank->id;

        }

        return config('commission.ranks.customer');
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
            if (+$rank->cat_id === 13) {
                $rank->rank_id = 1;
                $rank->paid_as_rank_id = 1;
            } else {

                $rank->rank_id = $this->getBgRank($volume);
                $rank->paid_as_rank_id = $rank->rank_id > +$rank->min_rank_id ? $rank->rank_id : $rank->min_rank_id;

            }

            $rank->rank_id = $rank->paid_as_rank_id; // added by jen
            if($rank->paid_as_rank_id > 2) {
                $rank->is_active = 1;
            }

            
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
                dv.gv = COALESCE(a.gv, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
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
                SET bg".$bgLevel."
        ";
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

    private function setCoachPoints()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    a.user_id,
                    IF(a.ppv >= @max_points, @max_points, a.ppv) ppv_capped, 
                    a.ppv
                FROM (
                    SELECT
                        t.user_id,
                        SUM(COALESCE(t.computed_cv, 0)) As ppv
                    FROM v_cm_transactions t
                    WHERE transaction_date BETWEEN @start_date AND DATE(@end_date)
                        AND t.`type` = 'product'
                        AND FIND_IN_SET(t.purchaser_catid, @affiliates)
                    GROUP BY t.user_id
                ) a
            ) AS a ON a.user_id = dv.user_id 
            LEFT JOIN (
                WITH RECURSIVE downline (user_id, parent_id, `level`, root_id) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`,
                        sponsorid root_id
                    FROM users u
                    WHERE u.levelid = 3
                        AND (
                            EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @customers))
                            OR EXISTS(
                                SELECT 1 
                                FROM v_cm_transactions t 
                                WHERE t.`type` = 'product' 
                                    AND t.transaction_date BETWEEN @start_date AND @end_date
                                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                                    AND t.user_id = u.id 
                            )
                        )
                        
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`,
                        downline.root_id
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE p.levelid = 3
                        AND (
                            EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.id AND FIND_IN_SET(cm.catid, @customers))
                            OR EXISTS(
                                SELECT 1 
                                FROM v_cm_transactions t 
                                WHERE t.`type` = 'product' 
                                    AND t.transaction_date BETWEEN @start_date AND @end_date
                                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                                    AND t.user_id = p.id 
                            )
                        )            
                )
                SELECT
                    a.root_id AS user_id,
                    SUM(IF(a.pcv > @max_points, @max_points, a.pcv)) pcv_capped,
                    SUM(a.pcv) pcv,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', a.user_id, 'uncapped', a.pcv, 'capped', IF(a.pcv > @max_points, @max_points, a.pcv))), 
                    ']') `users`
                FROM (
                    SELECT
                        d.root_id,
                        d.user_id,
                        SUM(COALESCE(t.computed_cv, 0)) AS pcv
                    FROM downline d
                    JOIN v_cm_transactions t ON t.user_id = d.user_id
                    WHERE t.transaction_date BETWEEN @start_date AND @end_date
                        AND t.`type` = 'product' 
                        AND FIND_IN_SET(t.purchaser_catid, @customers)
                    GROUP BY d.root_id, d.user_id
                ) a
                GROUP BY a.root_id   
            ) AS c ON c.user_id = dv.user_id
            SET
                dv.ppv = COALESCE(a.ppv, 0),
                dv.ppv_capped = COALESCE(a.ppv_capped, 0),
                dv.pcv = COALESCE(c.pcv, 0),
                dv.pcv_capped = COALESCE(c.pcv_capped, 0),
                dv.pcv_users = c.`users`,
                dv.coach_points = COALESCE(a.ppv_capped, 0) + COALESCE(c.pcv_capped, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function calculateRanksAndVolumes()
    {

    }

    private function setOrganizationPoints()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN (                
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`, coach_points) AS (
                    SELECT 
                        p.user_id,
                        p.sponsor_id AS parent_id,
                        p.user_id AS root_id,
                        0 AS `level`,
                        dv.coach_points AS coach_points
                    FROM cm_genealogy_placement p
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
                    
                    UNION ALL
                    
                    SELECT
                        p.user_id AS user_id,
                        p.sponsor_id AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`,
                        dv.coach_points AS coach_points
                    FROM cm_genealogy_placement p
                    JOIN downline ON downline.user_id = p.sponsor_id
                    JOIN cm_daily_volumes dv ON dv.user_id = p.user_id AND dv.volume_date = @end_date
                    WHERE downline.`level` < 5
                )
                SELECT 
                    d.root_id AS user_id,
                    SUM(d.coach_points) AS organization_points
                    /*,CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', d.user_id, 'coach_points', d.coach_points, 'level', d.`level`) ORDER BY d.`level`, d.user_id), 
                    ']') `users`*/
                FROM downline d
                WHERE d.root_id <> d.user_id
                GROUP BY d.root_id
            ) a ON a.user_id = dv.user_id
            SET
                dv.organization_points = a.organization_points
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function setTeamGroupPoints()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdv.user_id,
                    COALESCE(SUM(dv.organization_points), 0) team_group_points
                FROM cm_daily_volumes sdv
                JOIN users u ON u.sponsorid = sdv.user_id
                JOIN cm_daily_volumes dv ON dv.user_id = u.id AND dv.volume_date = @end_date AND dv.organization_points > 0
                WHERE sdv.volume_date = @end_date
                GROUP BY sdv.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.team_group_points = COALESCE(a.team_group_points, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setPreferredCustomerCount()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                WITH RECURSIVE downline (user_id, parent_id, `level`, root_id) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`,
                        sponsorid root_id
                    FROM users u
                    WHERE u.levelid = 3
                        AND (
                            EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @customers))
                            /*OR EXISTS(
                                SELECT 1 
                                FROM v_cm_transactions t 
                                WHERE t.`type` = 'product' 
                                    AND t.transaction_date BETWEEN @start_date AND @end_date
                                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                                    AND t.user_id = u.id 
                            )*/
                        )
                        
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`,
                        downline.root_id
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE p.levelid = 3
                        AND (
                            EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.id AND FIND_IN_SET(cm.catid, @customers))
                            /*OR EXISTS(
                                SELECT 1 
                                FROM v_cm_transactions t 
                                WHERE t.`type` = 'product' 
                                    AND t.transaction_date BETWEEN @start_date AND @end_date
                                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                                    AND t.user_id = p.id 
                            )*/
                        )
                )
                SELECT
                    a.root_id AS user_id,
                    COUNT(IF(a.pp >= @min_active_points, 1, NULL)) `count`,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', a.user_id, 'pp', a.pp)), 
                    ']') `users`
                FROM (
                    SELECT
                        d.root_id,
                        d.user_id,
                        COALESCE(SUM(t.computed_cv), 0) pp
                    FROM downline d
                    JOIN v_cm_transactions t ON t.user_id = d.user_id
                    WHERE t.`type` = 'product'
                        AND t.transaction_date BETWEEN @start_date AND @end_date
                        AND FIND_IN_SET(t.purchaser_catid, @customers)
                    GROUP BY d.root_id, d.user_id
                ) a
                JOIN oc_autoship oca ON oca.customer_id = a.user_id AND oca.is_active = 1
                GROUP BY a.root_id
            ) AS b ON b.user_id = dv.user_id 
            SET
                dv.preferred_customer_count = COALESCE(b.`count`, 0),
                dv.preferred_customer_users = b.`users`
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setReferralPointsFromNewPreferredCustomers()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                WITH RECURSIVE downline (user_id, parent_id, `level`, root_id) AS (
                    SELECT 
                        id AS user_id,
                        u.sponsorid AS parent_id,
                        1 AS `level`,
                        u.sponsorid root_id
                    FROM users u
                    WHERE u.levelid = 3
                        AND (
                            EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @customers))
                            /*OR EXISTS(
                                SELECT 1 
                                FROM v_cm_transactions t 
                                WHERE t.`type` = 'product' 
                                    AND t.transaction_date BETWEEN @start_date AND @end_date
                                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                                    AND t.user_id = u.id 
                            )*/
                        )
                        
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        d.`level` + 1 `level`,
                        d.root_id
                    FROM users p
                    JOIN downline d ON d.user_id = p.sponsorid
                    WHERE p.levelid = 3
                        AND (
                            EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.id AND FIND_IN_SET(cm.catid, @customers))
                            /*OR EXISTS(
                                SELECT 1 
                                FROM v_cm_transactions t 
                                WHERE t.`type` = 'product' 
                                    AND t.transaction_date BETWEEN @start_date AND @end_date
                                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                                    AND t.user_id = p.id 
                            )*/
                        )
                    
                )
                SELECT
                    a.root_id AS user_id,
                    COUNT(IF(a.pp >= @min_active_points, 1, NULL)) `count`,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', a.user_id, 'pp', a.pp, 'created_date', a.created_date, 'third_month_date', a.third_month_date, 'points', IF(a.pp >= @min_active_points, 1, 0))), 
                    ']') `users`
                FROM (
                    SELECT
                        d.root_id,
                        d.user_id,
                        COALESCE(SUM(t.computed_cv), 0) pp,
                        u.created_date,
                        LAST_DAY(DATE_ADD(u.created_date, INTERVAL 2 MONTH)) third_month_date
                    FROM downline d
                    JOIN users u ON u.id = d.user_id
                    JOIN v_cm_transactions t ON t.user_id = d.user_id
                    WHERE t.`type` = 'product'
                        AND t.transaction_date BETWEEN @start_date AND @end_date
                        AND FIND_IN_SET(t.purchaser_catid, @customers)
                        AND @end_date BETWEEN u.created_date AND LAST_DAY(DATE_ADD(u.created_date, INTERVAL 2 MONTH))
                    GROUP BY d.root_id, d.user_id
                ) a
                JOIN oc_autoship oca ON oca.customer_id = a.user_id AND oca.is_active = 1
                GROUP BY a.root_id
            ) AS b ON b.user_id = dv.user_id 
            SET
                dv.referral_preferred_customer_points = COALESCE(b.`count`, 0),
                dv.referral_preferred_customer_users = b.`users`
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setReferralPointsFromNewActiveEnrolledCoaches()
    {

        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdv.user_id,
                     /* same lng ang logic sa upgraded customer ug ang new affiliate,
                        need gihapon niyag preferred customer para ma 2 points.
                     */
                    -- COALESCE(SUM(IF(cus.user_id IS NOT NULL AND oca.customer_id IS NOT NULL, 2, 1)), 0) points, 
                    COALESCE(SUM(IF( dv.preferred_customer_count >= 1, 2, 1)), 0) points,
                    CONCAT('[',
                        GROUP_CONCAT(JSON_OBJECT('user_id', dv.user_id, 'has_upgraded', IF(cus.user_id IS NOT NULL, 1, 0), 'has_autoship',  IF(oca.customer_id IS NOT NULL, 1, 0), 'points', IF( dv.preferred_customer_count >= 1, 2, 1), 'preferred_customer_count', dv.preferred_customer_count)), 
                    ']') `users`
                FROM cm_daily_volumes sdv
                JOIN users u ON u.sponsorid = sdv.user_id
                JOIN cm_daily_volumes dv ON dv.user_id = u.id AND dv.volume_date = @end_date
                LEFT JOIN cm_customers cus ON cus.user_id = dv.user_id AND cus.enrolled_date BETWEEN u.created_date AND LAST_DAY(DATE_ADD(u.created_date, INTERVAL 2 MONTH))
                LEFT JOIN oc_autoship oca ON oca.customer_id = cus.user_id AND oca.is_active = 1
                WHERE sdv.volume_date = @end_date
                    AND @end_date BETWEEN u.created_date AND LAST_DAY(DATE_ADD(u.created_date, INTERVAL 2 MONTH))
                GROUP BY sdv.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.referral_enrolled_coach_points = COALESCE(a.points, 0),
                dv.referral_enrolled_coach_users = a.`users`
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
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

            $c = $this->getEnrolledCoachesRankCount($user_id);

            $volume->influencer_count = +$c->get("Influencer");
            $volume->silver_influencer_count = +$c->get("Silver Influencer");
            $volume->gold_influencer_count = +$c->get("Gold Influencer");
            $volume->platinum_influencer_count = +$c->get("Platinum Influencer");
            $volume->diamond_influencer_count = +$c->get("Diamond Influencer");

            $rf = $this->getReferralPointsFromRankAdvancement($user_id);

            $volume->referral_rank_advancement_points = +$rf['points'];
            $volume->referral_rank_advancement_users = $rf['users'];

            $volume->referral_points = $volume->referral_preferred_customer_points + $volume->referral_enrolled_coach_points + $volume->referral_rank_advancement_points;

            $volume->save();

            $rank = $volume->rank;

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

            $diamond_influencer_count = +$volume->diamond_influencer_count;
            $platinum_influencer_count = $diamond_influencer_count + +$volume->platinum_influencer_count;
            $gold_influencer_count = $platinum_influencer_count + +$volume->gold_influencer_count;
            $silver_influencer_count = $gold_influencer_count + +$volume->silver_influencer_count;
            $influencer_count = $silver_influencer_count + +$volume->influencer_count;

            if (
                +$volume->preferred_customer_count >= +$rank->preferred_customer_count_requirement
                && +$volume->referral_points >= +$rank->referral_points_requirement
                && +$volume->organization_points >= +$rank->organization_points_requirement
                && +$volume->team_group_points >= +$rank->team_group_points_requirement
                && $gold_influencer_count >= +$rank->gold_influencer_count_requirement
                && ($silver_influencer_count - $rank->gold_influencer_count_requirement) >= +$rank->silver_influencer_count_requirement
                && ($influencer_count - $rank->gold_influencer_count_requirement - $rank->silver_influencer_count_requirement) >= +$rank->influencer_count_requirement
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

    private function initializeBlgVolumes()
    {
        $sql = "
            INSERT INTO cm_daily_volumes (
                user_id, 
                volume_date, 
                pv,
                gv, 
                pv_current_date,
                D,
                group_volume_right_leg,
                active_personal_enrollment_count,
                active_personal_enrollment_users,
                level
            )

            WITH RECURSIVE downline (user_id, parent_id, `level`, compress_level) AS (
                SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    downline.compress_level + IF(u.active = 'Yes', 1, 0),
                    0 AS `level`
                FROM users
                WHERE id = @root_user_id AND levelid = 3
                
                UNION ALL
                
                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    downline.`level` + 1 `level`,
                    downline.compress_level + IF(u.active = 'Yes', 1, 0)
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
                0 pv_current_date,
                0 group_volume_left_leg,
                0 group_volume_right_leg,
                0 active_personal_enrollment_count,
                NULL active_personal_enrollment_users,
                d.level
            FROM downline d
            ON DUPLICATE KEY UPDATE
                pv = 0,
                gv = 0,
                pv_current_date = 0,
                group_volume_left_leg = 0,
                group_volume_right_leg = 0,
                active_personal_enrollment_count = 0,
                active_personal_enrollment_users = NULL,
                level = d.level,
                updated_at = CURRENT_TIMESTAMP()
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function initializeVolumes()
    {
        $sql = "
            INSERT INTO cm_daily_volumes (
                user_id, 
                volume_date, 
                ppv,
                ppv_capped,
                pcv,
                pcv_capped,
                pcv_users,
                coach_points,
                organization_points,
                team_group_points,
                referral_preferred_customer_points,
                referral_preferred_customer_users,
                referral_enrolled_coach_points,
                referral_enrolled_coach_users,
                referral_rank_advancement_points,
                referral_rank_advancement_users,
                referral_points,
                personally_enrolled_retention_rate,
                customer_retention_rate,
                organization_retention_rate,
                preferred_customer_count,
                preferred_customer_users,
                influencer_count,
                silver_influencer_count,
                gold_influencer_count,
                platinum_influencer_count,
                diamond_influencer_count,
                level
            )

            WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    0 AS `level`
                FROM users
                WHERE id = @root_user_id AND levelid = 3
                
                UNION ALL
                
                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    downline.`level` + 1 `level`
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                WHERE p.levelid = 3
            )
            SELECT
                d.user_id, 
                @end_date volume_date, 
                0 ppv,
                0 ppv_capped,
                0 pcv,
                0 pcv_capped,
                NULL pcv_users,
                0 coach_points,
                0 organization_points,
                0 team_group_points,
                0 referral_preferred_customer_points,
                NULL referral_preferred_customer_users,
                0 referral_enrolled_coach_points,
                NULL referral_enrolled_coach_users,
                0 referral_rank_advancement_points,
                NULL referral_rank_advancement_users,
                0 referral_points,
                0 personally_enrolled_retention_rate,
                0 customer_retention_rate,
                0 organization_retention_rate,
                0 preferred_customer_count,
                NULL preferred_customer_users,
                0 influencer_count,
                0 silver_influencer_count,
                0 gold_influencer_count,
                0 platinum_influencer_count,
                0 diamond_influencer_count,
                d.level
            FROM downline d
            WHERE EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = d.user_id AND FIND_IN_SET(c.catid, @affiliates))            
            ON DUPLICATE KEY UPDATE
                ppv = 0,
                ppv_capped = 0,
                pcv = 0,
                pcv_capped = 0,
                pcv_users = NULL,
                coach_points = 0,
                organization_points = 0,
                team_group_points = 0,
                referral_preferred_customer_points = 0,
                referral_preferred_customer_users = NULL,
                referral_enrolled_coach_points = 0,
                referral_enrolled_coach_users = NULL,
                referral_rank_advancement_points = 0,
                referral_rank_advancement_users = NULL,
                referral_points = 0,
                personally_enrolled_retention_rate = 0,
                customer_retention_rate = 0,
                organization_retention_rate = 0,
                preferred_customer_count = 0,
                preferred_customer_users = NULL,
                influencer_count = 0,
                silver_influencer_count = 0,
                gold_influencer_count = 0,
                platinum_influencer_count = 0,
                diamond_influencer_count = 0,
                level = d.level,
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
                is_active,
                is_system_active,
                is_qualified_trader_or_higher,
                rank_last_90_days
            )
            SELECT 
                dv.user_id, 
                dv.id AS volume_id, 
                dv.volume_date AS rank_date, 
                1 AS rank_id, 
                1 AS min_rank_id, 
                1 AS paid_as_rank, 
                0 AS is_active,
                0 AS is_system_active,
                0 AS is_qualified_trader_or_higher,
                1 AS rank_last_90_days
            FROM cm_daily_volumes dv
            WHERE volume_date = @end_date
            ON DUPLICATE KEY UPDATE 
                min_rank_id = 1,
                rank_id = 1,
                paid_as_rank_id = 1,
                is_active = 0,
                is_system_active = 0,
                is_qualified_trader_or_higher = 0,
                rank_last_90_days = 1,
                volume_id = VALUES(volume_id),
                updated_at = CURRENT_TIMESTAMP();
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
                user_id, 
                id AS volume_id, 
                volume_date AS rank_date, 
                1 AS rank_id, 
                1 AS min_rank_id, 
                1 AS paid_as_rank, 
                0 AS is_active,
                0 AS is_system_active
            FROM cm_daily_volumes
            WHERE volume_date = @end_date
            ON DUPLICATE KEY UPDATE 
                min_rank_id = 1,
                rank_id = 1,
                paid_as_rank_id = 1,
                is_active = 0,
                is_system_active = 0,
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
            WHERE a.date_achieved = @end_date AND a.is_migrated = 0
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