<?php

namespace Commissions;

use App\DailyRank;
use App\DailyVolume;
use App\Rank;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Carbon\Carbon;

final class VolumesAndRanks extends Console
{
    const IS_DEBUG = false;
    const ROOT_USER_ID = 3;
    const MIN_ACTIVE_PV = 75;
    const MIN_ROLLOVER_BV = 225;
    const MAX_LEG_RULE = 0.5;

    protected $db;
    protected $end_date;
    protected $start_date;
    protected $affiliates;
    protected $customers;
    protected $rank_requirements;

    public function __construct($end_date = null)
    {
        $this->db = DB::connection()->getPdo();
        $this->affiliates = config('commission.member-types.affiliates');
        $this->customers = config('commission.member-types.customers');

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

            $this->log("Root User ID: " . static::ROOT_USER_ID);
            $this->log("Minimum Active PV: " . static::MIN_ACTIVE_PV);

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

            $this->log("Setting Personal (Purchase) Sales Volume");
            $this->setPersonalPurchaseSalesVolume();

            $this->log("Setting Category ID");
            $this->setCategoryID(); 

            $this->log("Setting If Member Is Active");
            $this->setIfMemberIsActive();

            $this->log("Setting Minimum Rank");
            $this->setMinimumRank();

            // Binary Tree
            $this->log("Setting Today's Personal (Purchase) Sales Volume");
            $this->setCurrentDatePersonalVolume();

            $this->log("Setting Group Volumes");
            $this->setGroupVolume();

            $this->log("Setting Group Volume Left Leg");
            $this->setGroupVolumeLeftLeg();

            $this->log("Setting Group Volume Right Leg");
            $this->setGroupVolumeRightLeg();

            $this->log("Setting Rollover Volume");
            $this->setRollOver();
            
            $this->log("Setting Total Group Volume");
            $this->setTotalGroupVolumeOnLegs();

            $this->log("Setting Greater and Lesser Volume");
            $this->setGreaterAndLesserVolume();

            $this->log("Setting Active Personal Enrollments");
            $this->setActivePersonalEnrollments();

            $this->log("Setting Paid-as Rank");
            $this->setRanks();

            $this->log("Set Qualified Trader or Higher Rank");
            $this->setQualifiedTraderRank();

            $this->log("Set Rank Last 90 Days");
            $this->setRankLast90Days();

            $this->log("Deleting Previous Highest Achieved Rank This Month");
            $this->deletePreviousHighestAchievedRanksThisMonth();

            $this->log("Updating Previous Highest Achieved Rank This Month");
            $this->updatePreviousHighestReachievedRanksThisMonth();

        }, 3);

		$this->log($this->getEndDate());
		$this->log(date("Y-m-d"));
        if ($this->getEndDate() === date("Y-m-d")) {
            Artisan::call('commission:process-binary-tree');
        }

    }

    private function setMainParameters()
    {
        $this->db->prepare("
            SET @root_user_id = :root_user_id,
                @start_date = :start_date,
                @end_date = :end_date,
                @affiliates = :affiliates,
                @customers = :customers,
                @min_active_pv = :min_active_pv,
                @min_rollover_bv = :min_rollover_bv
            ")
            ->execute([
                ':root_user_id' => static::ROOT_USER_ID,
                ':start_date' => $this->getStartDate(),
                ':end_date' => $this->getEndDate(),
                ':customers' => $this->customers,
                ':affiliates' => $this->affiliates,
                ':min_active_pv' => static::MIN_ACTIVE_PV,
                ':min_rollover_bv' => static::MIN_ROLLOVER_BV,
            ]);

        if (static::IS_DEBUG) {
            $stmt = $this->db->prepare("
                SELECT
                    @root_user_id,
                    @start_date,
                    @end_date,
                    @affiliates,
                    @customers,
                    @min_active_pv,
                    @min_rollover_bv
            ");

            $stmt->execute();

            $this->log_debug($stmt->fetch());
        }
    }

    private function setPersonalPurchaseSalesVolume()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    t.user_id,
                    SUM(COALESCE(t.computed_cv, 0)) As ps
                FROM v_cm_transactions t
                WHERE DATE(transaction_date) BETWEEN @start_date AND @end_date
                 --   AND t.`type` = 'product'
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

	private function isCustomer($user_id) {

        $sql = "
            SELECT * FROM categorymap 
            WHERE userid = :user_id AND FIND_IN_SET(catid, @customers)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if(count($stmt->fetchAll(PDO::FETCH_ASSOC)) > 0) {
            return true;
        }
        
        return false;
	}

    private function setRanks()
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

				$rank->rank_id = $this->getRank($volume);
				$last_month_paid_as_rank = $this->getPreviousMonthPaidAsRank($user_id);
				if (!+$last_month_paid_as_rank) {
					$rank->paid_as_rank_id = $rank->rank_id > +$rank->min_rank_id ? $rank->rank_id : $rank->min_rank_id;
				} else {
					$rank->paid_as_rank_id = !+$last_month_paid_as_rank ?  $rank->paid_as_rank_id : +$last_month_paid_as_rank;
				}

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

    private function getPreviousMonthPaidAsRank($user_id)
    {
        $previous_month = Carbon::today()->subMonth()->endOfMonth()->format("Y-m");
        $sql = "
            SELECT MAX(paid_as_rank_id) FROM cm_daily_ranks WHERE DATE_FORMAT(rank_date, '%Y-%m') = '$previous_month' AND user_id = $user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    private function getNextDelivery($user_id)
    {
        $sql = "
            SELECT * FROM oc_autoship 
            WHERE customer_id = :user_id AND DATE(deliverydate) >= @end_date AND is_active = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if(count($stmt->fetchAll(PDO::FETCH_ASSOC)) > 0) {
            return 1;
        }
        
        return 0;
    }

    private function getRank(DailyVolume $volume)
    {
        foreach ($this->rank_requirements as $rank) {

            if (
                +$volume->rank->is_active
                && (+$volume->total_group_volume_left_leg * static::MAX_LEG_RULE) >= +$rank->binary_volume_requirement
                && (+$volume->total_group_volume_right_leg * static::MAX_LEG_RULE) >= +$rank->binary_volume_requirement
                && +$volume->active_personal_enrollment_count >= +$rank->active_personal_enrollment_requirement
            ) return +$rank->id;

        }

        return config('commission.ranks.customer');
    }

    private function saveAchievedRank($user_id, $rank_id)
    {
        $sql = "
            INSERT INTO cm_achieved_ranks (user_id, rank_id, date_achieved, recent_date_achieved, reachieved_date) 
            SELECT 
                :user_id,
                r.id,
                @end_date,
                @end_date,
                @end_date
            FROM cm_ranks r
            WHERE r.id <= :rank_id
            ON DUPLICATE KEY UPDATE
                date_achieved = IF(date_achieved < VALUES(date_achieved), date_achieved, VALUES(date_achieved)),
                recent_date_achieved = IF(VALUES(recent_date_achieved) > recent_date_achieved AND @end_date = LAST_DAY(@end_date), VALUES(recent_date_achieved), recent_date_achieved),
                reachieved_date = IF(reachieved_date < VALUES(reachieved_date), reachieved_date, VALUES(reachieved_date))
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':rank_id', $rank_id);
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
                AND dr.rank_date >= a.date_achieved
                /*AND a.is_migrated = 0*/
            ;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->rowCount();

        $this->log("{$rows} row(s) deleted");
    }

    private function updatePreviousHighestReachievedRanksThisMonth()
    {
        $sql = "
            UPDATE cm_achieved_ranks AS a
            JOIN cm_daily_ranks dr ON dr.user_id = a.user_id  AND dr.rank_date = @end_date
            SET a.reachieved_date = NULL
            WHERE a.reachieved_date BETWEEN @start_date AND @end_date
                AND a.rank_id > dr.paid_as_rank_id
                AND dr.rank_date >= a.date_achieved
                /*AND a.is_migrated = 0*/
            ;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->rowCount();

        $this->log("{$rows} row(s) updated");
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

    private function getRankRequirements()
    {
        $this->rank_requirements = Rank::orderBy("id", "desc")->get();
    }

    private function setIfMemberIsActive()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            JOIN users u ON u.id = dv.user_id
            SET 
                dr.is_active = (memberActive(u.id, @end_date)),
                dr.is_system_active = IF(u.active='Yes', 1, 0)
            WHERE dv.volume_date = @end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setMinimumRank()
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

    private function setQualifiedTraderRank()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN (
                SELECT cdr.user_id,COUNT(cdr.user_id) AS is_qualified  
                FROM cm_daily_ranks cdr
                WHERE cdr.`rank_id` >= 7 -- qualified trader minimum rank
                AND cdr.`rank_date` = LAST_DAY(cdr.`rank_date`)
                AND cdr.`rank_date` BETWEEN DATE_SUB(@end_date, INTERVAL 90 DAY) AND @end_date
                GROUP BY cdr.user_id
            ) AS a ON a.user_id = dr.user_id
            SET is_qualified_trader_or_higher = IF(a.is_qualified = 3, 1, 0)
            WHERE dr.rank_date = @end_date
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    public function setRankLast90Days()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN (
                SELECT cdr.user_id,MAX(cdr.rank_id) AS rank_id
                FROM cm_daily_ranks cdr
                WHERE cdr.`rank_id` >= 5 -- junior trader minimum rank
                AND cdr.`rank_date` = LAST_DAY(cdr.`rank_date`)
                AND cdr.`rank_date` BETWEEN DATE_SUB(@end_date, INTERVAL 90 DAY) AND @end_date
            ) AS a ON a.user_id = dr.user_id
            SET rank_last_90_days = IF(a.rank_id > dr.rank_id, a.rank_id, dr.rank_id)
            WHERE dr.rank_date = @end_date
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setCategoryID()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            SET
                dr.cat_id = (
                    SELECT 
                        cm.catid 
                    FROM categorymap cm 
                    WHERE cm.userid = dr.user_id
                    ORDER BY FIND_IN_SET(cm.catid, @affiliates) DESC
                    LIMIT 1
                )
            WHERE dr.rank_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function initializeVolumes()
    {
        $sql = "
            INSERT INTO cm_daily_volumes (
                user_id, 
                volume_date, 
                pv,
                gv, 
                pv_current_date,
                group_volume_left_leg,
                group_volume_right_leg,
                active_personal_enrollment_count,
                active_personal_enrollment_users,
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

    private function setCurrentDatePersonalVolume()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
            LEFT JOIN (
                SELECT
                    t.user_id,
                    SUM(COALESCE(t.computed_cv, 0)) AS pv
                FROM v_cm_transactions t
                WHERE t.transaction_date = @end_date
                        AND t.`type` = 'product'
                GROUP BY t.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.pv_current_date = COALESCE(a.pv, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setGroupVolume()
    {
        $users = $this->getGroupVolume();

        foreach ($users as $user) {
            $this->updateGroupVolume($user['root_id'], +$user['gv']);
        }
    }

    private function getGroupVolume()
    {
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, root_id, binary_position, `level`) AS (
                SELECT 
                    user_id,
                    parent_id,
                    user_id AS root_id,
                    cm_genealogy_binary.position AS binary_position,
                    0 AS `level`
                FROM cm_genealogy_binary
                
                UNION ALL
                
                SELECT
                    cg.user_id,
                    cg.parent_id,
                    downline.root_id,
                    cg.position AS binary_position,
                    downline.`level` + 1 `level`
                FROM cm_genealogy_binary cg 
                INNER JOIN downline ON cg.parent_id = downline.user_id
            )
            SELECT
                d.root_id,
                SUM(IFNULL(dv.pv, 0)) AS gv
            FROM downline d
            JOIN cm_daily_volumes dv ON dv.user_id = d.user_id AND dv.volume_date = @end_date
            GROUP BY d.root_id
            HAVING gv > 0
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
        
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function updateGroupVolume($user_id, $gv)
    {
        $sql = "
            UPDATE cm_daily_volumes dv
                SET dv.gv = $gv
            WHERE dv.volume_date = @end_date AND dv.user_id = $user_id
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setGroupVolumeLeftLeg()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    gb.parent_id AS user_id,
                    SUM(dv.gv) gv
                FROM cm_daily_volumes dv
                JOIN cm_genealogy_binary gb ON gb.user_id = dv.user_id
                WHERE dv.volume_date = @end_date AND gb.position = 0
                GROUP BY gb.parent_id
            ) a ON a.user_id = dv.user_id
            SET 
                dv.group_volume_left_leg = IFNULL(a.gv, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }
    
    private function setGroupVolumeRightLeg()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    gb.parent_id AS user_id,
                    SUM(dv.gv) gv
                FROM cm_daily_volumes dv
                JOIN cm_genealogy_binary gb ON gb.user_id = dv.user_id
                WHERE dv.volume_date = @end_date AND gb.position = 1
                GROUP BY gb.parent_id
            ) a ON a.user_id = dv.user_id
            SET 
                dv.group_volume_right_leg = IFNULL(a.gv, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setGreaterAndLesserVolume()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdv.user_id,
                    CASE
                        WHEN sdv.total_group_volume_left_leg > sdv.total_group_volume_right_leg THEN sdv.total_group_volume_left_leg
                        ELSE sdv.total_group_volume_right_leg
                    END greater_volume,
                    CASE
                        WHEN sdv.total_group_volume_left_leg < sdv.total_group_volume_right_leg THEN sdv.total_group_volume_left_leg
                        ELSE sdv.total_group_volume_right_leg
                    END lesser_volume
                FROM cm_daily_volumes sdv
                WHERE sdv.volume_date = @end_date
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.greater_volume = COALESCE(a.greater_volume, 0),
                dv.lesser_volume = COALESCE(a.lesser_volume, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setRollOver()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN (
                SELECT
                    cdv.user_id,
                    cdv.total_group_volume_left_leg,
                    cdv.total_group_volume_right_leg,
                    cdv.lesser_volume
                FROM cm_daily_volumes cdv
                WHERE cdv.volume_date = LAST_DAY(@end_date - INTERVAL 1 MONTH)
            ) a ON a.user_id = dv.user_id
            SET
                dv.rollover_volume_left = IF(a.lesser_volume < @min_rollover_bv, a.total_group_volume_left_leg, COALESCE(a.total_group_volume_left_leg, 0) - COALESCE(a.lesser_volume, 0)),
                dv.rollover_volume_right = IF(a.lesser_volume < @min_rollover_bv, a.total_group_volume_right_leg, COALESCE(a.total_group_volume_right_leg, 0) - COALESCE(a.lesser_volume, 0))
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }
    
    private function setTotalGroupVolumeOnLegs()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            SET
                dv.total_group_volume_left_leg = COALESCE(dv.rollover_volume_left, 0) + COALESCE(dv.group_volume_left_leg, 0),
                dv.total_group_volume_right_leg = COALESCE(dv.rollover_volume_right, 0) + COALESCE(dv.group_volume_right_leg, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setActivePersonalEnrollments()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
            LEFT JOIN (
                SELECT
                    u.sponsorid AS user_id,
                    COUNT(u.id) AS active_personal_enrollment_count,
                    CONCAT('[', 
                    GROUP_CONCAT(JSON_OBJECT('user_id', u.id)), 
                    ']') `users`
                FROM users u
                JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = @end_date
                WHERE dr.is_active = 1
                GROUP BY u.sponsorid
            ) AS a ON a.user_id = dv.user_id
            SET
                dv.active_personal_enrollment_count =  COALESCE(a.active_personal_enrollment_count, 0),
                dv.active_personal_enrollment_users = a.`users`
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    public static function getNextRankRequirementsByDailyVolume(DailyVolume $volume, Rank $next_rank)
    {
        if ($next_rank === null || $volume === null) return [];

        $needs = [];

        $pv_requirement = $next_rank->pv_requirement - $volume->pv;
        $left_leg_volume_requirement = $next_rank->binary_volume_requirement - ($volume->total_group_volume_left_leg * 0.50);
        $rigt_leg_volume_requirement = $next_rank->binary_volume_requirement - ($volume->total_group_volume_right_leg * 0.50);
        $ape_count_requirement = $next_rank->active_personal_enrollment_requirement - $volume->active_personal_enrollment_count;

        if ($pv_requirement > 0) {
            $needs[] = [
                'value' => $pv_requirement,
                'description' => 'Personal Volume',
            ];
        }

        if ($left_leg_volume_requirement > 0) {
            $needs[] = [
                'value' => $left_leg_volume_requirement * 2,
                'description' => 'Left Leg Volume',
            ];
        }

        if ($rigt_leg_volume_requirement > 0) {
            $needs[] = [
                'value' => $rigt_leg_volume_requirement * 2,
                'description' => 'Right Leg Volume',
            ];
        }

        if ($ape_count_requirement > 0) {
            $needs[] = [
                'value' => $ape_count_requirement,
                'description' => 'Active Personal Enrollment',
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