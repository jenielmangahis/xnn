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
        $this->affiliates = '14,15';//config('commission.member-types.affiliates');
        $this->customers = '13';//config('commission.member-types.customers');
		//$this->root_user_id = 3;
		$this->root_user_id = 1450;

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
			
            $this->log("Deleting ranks and volumes records of affiliates");
            $this->deleteAffiliateRecords();

            $this->log("Deleting achieved ranks");
            $this->deleteAchievedRanks();

            $this->log("Getting rank requirements");
            $this->getRankRequirements();

            $this->log('Initializing Volumes');
            $this->initializeVolumes();

            $this->log('Initializing Ranks');
			$this->initializeRanks();
			
            $this->log("Setting If Member Is Active");
			$this->setIfMemberIsActive();

            $this->log('Set Personal Energy Accounts (PEA)');
			$this->setPersonalEnergyAccountsCount();

            $this->log('Set Flowing Personal Energy Accounts (PEA) Count');
			$this->setPeaFlowingCount();
			
            $this->log('Set Team Accounts (TA)');
			$this->setTeamAccountsCount();

			$this->log("Setting Minimum Rank");
            $this->setMinimumRank();

            $this->log('Set Ranks');
			$this->setRanks();

            $this->log("Set Career Title");
            $this->setCareerTitle();

            $this->log("Set Canceled Users Rank");
            $this->setCanceledRank();

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
				@valid_energy_account_types = :valid_energy_account_types
            ")
            ->execute([
                ':root_user_id' => $this->root_user_id,
                ':start_date' => $this->getStartDate(),
                ':end_date' => $this->getEndDate(),
                ':customers' => $this->customers,
                ':affiliates' => $this->affiliates,
                ':valid_energy_account_types' => '4,5,6',
            ]);

        if (false) {
            $stmt = $this->db->prepare("
                SELECT
                    @root_user_id,
                    @start_date,
                    @end_date,
                    @affiliates,
					@customers,
					@valid_energy_account_types
            ");

            $stmt->execute();

            $this->log_debug($stmt->fetch());
        }
	}
	
	private function setPersonalEnergyAccountsCount()
	{
		$sql = "
			UPDATE cm_daily_volumes cdv

			LEFT JOIN (
				SELECT 
					s.sponsor_id AS sponsorid,
					COUNT(s.customer_id) AS `count`
				FROM cm_energy_accounts s
				WHERE FIND_IN_SET(s.status, @valid_energy_account_types)
				GROUP BY s.sponsor_id
			) AS pea ON cdv.user_id = pea.sponsorid

			SET cdv.pea = COALESCE(pea.`count`, 0)

			WHERE cdv.volume_date = @end_date
		";

		$sql = "UPDATE cm_daily_volumes cdv

			LEFT JOIN (
				SELECT
				  acc.sponsor_id AS sponsorid,
				  COUNT(acc.id) AS count
				FROM cm_energy_accounts acc
				WHERE EXISTS (SELECT
				    1
				  FROM cm_energy_account_logs l
				  WHERE l.energy_account_id = acc.id
				  AND FIND_IN_SET(l.current_status, @valid_energy_account_types)
				  AND l.created_date <= @end_date)
				AND NOT EXISTS (SELECT
				    1
				  FROM cm_energy_account_logs l
				  WHERE l.energy_account_id = acc.id
				  AND FIND_IN_SET(l.current_status, '7')
				  AND l.created_date <= @end_date)
				GROUP BY acc.sponsor_id
			) AS pea ON cdv.user_id = pea.sponsorid

			SET cdv.pea = COALESCE(pea.`count`, 0)

			WHERE cdv.volume_date = @end_date";

        $stmt = $this->db->prepare($sql);
		$stmt->execute();
	}

	private function setPeaFlowingCount()
	{
		$sql = "UPDATE cm_daily_volumes cdv

			LEFT JOIN (
				SELECT
				  acc.sponsor_id AS sponsorid,
				  COUNT(acc.id) AS count
				FROM cm_energy_accounts acc
				WHERE EXISTS (SELECT
				    1
				  FROM cm_energy_account_logs l
				  WHERE l.energy_account_id = acc.id
				  AND FIND_IN_SET(l.current_status, '5,6')
				  AND l.created_date <= @end_date)
				AND NOT EXISTS (SELECT
				    1
				  FROM cm_energy_account_logs l
				  WHERE l.energy_account_id = acc.id
				  AND FIND_IN_SET(l.current_status, '7')
				  AND l.created_date <= @end_date)
				GROUP BY acc.sponsor_id
			) AS pea ON cdv.user_id = pea.sponsorid

			SET cdv.pea_flowing  = COALESCE(pea.`count`, 0)

			WHERE cdv.volume_date = @end_date";

        $stmt = $this->db->prepare($sql);
		$stmt->execute();
	}

	private function setTeamAccountsCount()
	{
		$sql = "
			UPDATE cm_daily_volumes cdv
			
			LEFT JOIN (
				WITH RECURSIVE downline (user_id, parent_id, root_id, `level`) AS (
					SELECT 
						id AS user_id,
						sponsorid AS parent_id,
						id AS root_id,
						0 AS `level`
					FROM users
					WHERE levelid = 3
					
					UNION ALL
					
					SELECT
						p.id AS user_id,
						p.sponsorid AS parent_id,
						downline.root_id,
						downline.`level` + 1 `level`
					FROM users p
					INNER JOIN downline ON p.sponsorid = downline.user_id
					WHERE p.levelid = 3
					AND EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = p.id AND FIND_IN_SET(c.catid, @affiliates))
				)
				SELECT 
					d.root_id AS rootid,
					SUM(cdv.pea) AS ta
				FROM downline d
				INNER JOIN cm_daily_volumes cdv ON d.user_id = cdv.user_id
				WHERE 
                -- d.root_id <> d.user_id /* removing this filter to include user's PEA in TA - Jen */
				-- AND 
                cdv.volume_date = @end_date
				GROUP BY d.root_id
			) AS ta ON cdv.user_id = ta.rootid

			SET 
				cdv.ta = ta.ta

			WHERE cdv.volume_date = @end_date
		";

        $stmt = $this->db->prepare($sql);
		$stmt->execute();
	}

	private function setRanks()
	{
		$sql = "
			SELECT 
				cdr.is_active,
				cdr.is_canceled,
				cdv.user_id,
				cdv.pea,
				GROUP_CONCAT(a.ta) AS tas,
				SUM(a.ta) AS ta_capped,
				cdv.level
			FROM cm_daily_volumes cdv
			LEFT JOIN users u ON cdv.user_id = u.sponsorid AND EXISTS(SELECT 1 FROM cm_affiliates ca WHERE ca.affiliated_date <= @end_date AND ca.user_id = u.id)
			LEFT JOIN 
				(
					SELECT ta, user_id FROM cm_daily_volumes cdv WHERE cdv.volume_date = @end_date
				) AS a ON a.user_id = u.id
			INNER JOIN cm_daily_ranks cdr ON cdr.user_id = cdv.user_id
					AND cdr.rank_date = @end_date
			WHERE (cdv.user_id <> u.id OR u.id IS NULL)
			AND cdv.volume_date = @end_date
			GROUP BY cdv.user_id
			ORDER BY cdv.`level` DESC, cdv.user_id DESC";

		$stmt = $this->db->prepare($sql);
		$stmt->execute();

		$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$level = null;

		foreach ($users as $user) {
			if ($level !== $user['level']) {
                $level = $user['level'];
                $this->log("Processing Level $level");
			}

            $user_id = +$user['user_id'];
            $users_tas = explode(',', $user['tas']);
			$ta_capped = +$user['ta_capped'];

			$rank_id = 1;
			
			$mar = 0;
            $is_canceled = +$user['is_canceled'];

            if(!$is_canceled) {
                do {

                    $potential_rank_id = $this->getRank($user_id, $user['pea'], $ta_capped);

                    if ($potential_rank_id === 1) {
                        break;
                    }

                    $mar_ret = $this->applyMAR($potential_rank_id, $users_tas);

                    $ta_capped = $mar_ret['ta_capped'];
                    $mar = $mar_ret['mar'];

                    $rank_id = $this->getRank($user_id, $user['pea'], $ta_capped);

                    if ($user_id == '1454') {
                        $this->log($rank_id);
                    }


                } while ($potential_rank_id != $rank_id);
            }


			$volume = DailyVolume::date($this->getEndDate())->ofMember($user_id)->firstOrFail();
			$volume->qta = $ta_capped;
			$volume->mar = $mar;
			$volume->save();

            $rank = $volume->rank;
            $rank->rank_id = $rank_id;
            $rank->paid_as_rank_id = $rank_id > +$rank->min_rank_id ? $rank_id : +$rank->min_rank_id;

            $rank->save();

            $this->saveAchievedRank($user_id, $rank_id);
            $this->saveCareerRank($user_id, $rank_id);

		}
	}

	private function applyMAR($rank_id, $users_tas)
	{
		$marArr = [
            1 => 0,
            2 => 0,
            3 => 0,
			4 => 0,
			5 => 0,
            6 => 48,
            7 => 90,
            8 => 210,
            9 => 420,
            10 => 900,
            11 => 2100,
			12 => 4200,
        ];

        $ta_capped = 0;
		$mar = 0;

        foreach ($users_tas as $user_ta) {
            $m = $marArr[$rank_id];
            if ($m > 0 && $user_ta > $m) {
				$ta_capped += $m;
				$mar = $m;
            } else {
                $ta_capped += $user_ta;
            }
        }

        return ['ta_capped' => $ta_capped, 'mar' => $mar];
	}

    private function getRank($user_id, $pea, $qta)
    {
        $rankid = 1;

		$c = $this->getMaxRankPerLegCount($user_id);
		$r = config('commission.ranks');

		if ($pea >= 400 && $qta >= 7000) { //founding leader

            $natLeader = +$c->get($r['national-leader']);
            $presLeader = +$c->get($r['president-leader']);

			if ($natLeader >= 2 && $presLeader >= 2) {
				return 12;
			}
		}

		if ($pea >= 250 && $qta >= 3500) { //ceo leader

            $natLeader = +$c->get($r['national-leader']);
            $globalLeader = +$c->get($r['global-leader']);

			if ($natLeader >= 1 && $globalLeader >= 2) {
				return 11;
			}
		} 

		if ($pea >= 150 && $qta >= 1500) { //president leader

            $natLeader = +$c->get($r['national-leader']);

			if ($natLeader >= 2) {
				return 10;
			}
		} 

		if ($pea >= 100 && $qta >= 700) { //global leader

            $groupLeader = +$c->get($r['group-leader']);

			if ($groupLeader >= 2) {
				return 9;
			}
		} 

		if ($pea >= 75 && $qta >= 350) { //national leader

            $teamLeader = +$c->get($r['team-leader']);

			if ($teamLeader >= 2) {
				return 8;
			}
		} 

		if ($pea >= 50 && $qta >= 150) { //group leader

            $watt60 = +$c->get($r['watt-60']);

			if ($watt60 >= 2) {
				return 7;
			}
		} 

		if ($pea >= 40 && $qta >= 80) {

            $watt30 = +$c->get($r['watt-30']);

			if ($watt30 >= 2) {
				return 6;
			}
		}

		if ($pea >= 25 && $qta >= 35) { //watt 60
			return 5;
		} 

		if ($pea >= 12 && $qta >= 18) { //watt 30
			return 4;
		} 

		if ($pea >= 9 && $qta >= 6) { //watt 15
			return 3;
		}

		if ($pea >= 3) { //watt
			return 2;
		}

		return $rankid;

    }

    private function getMaxRankPerLegCount($user_id)
    {
        $sql = "
            SELECT
                rank_id,
                COUNT(1) AS `count`
            FROM (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`) AS (
                    SELECT 
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        u.id AS root_id,
                        1 AS `level`
                    FROM users u
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = @end_date AND u.sponsorid = :user_id
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`
                    FROM users u
                    JOIN downline ON u.sponsorid = downline.user_id
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = @end_date
                )
                SELECT
                    MAX(dr.rank_id) rank_id,
                    d.root_id 
                FROM downline AS d 
                JOIN cm_daily_ranks dr ON dr.user_id = d.user_id
                WHERE dr.rank_date = @end_date 
                GROUP BY d.root_id
            ) max_rank_per_leg
            GROUP BY rank_id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return collect($result)->mapWithKeys(function ($item) {
            return [(int)$item['rank_id'] => $item['count']];
        });
    }

    private function setIfMemberIsActive()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
			JOIN users u ON u.id = dv.user_id
            JOIN cm_affiliates a ON a.user_id = dv.user_id
            SET 
				dr.is_system_active = (u.active = 'Yes'),
				dr.is_active = IF (
                    @end_date BETWEEN a.affiliated_date AND DATE_ADD(a.affiliated_date, INTERVAL 90 DAY), 1, -- First 90 Days
                    (SELECT COUNT(cea.id) FROM cm_energy_accounts cea WHERE 
                      cea.sponsor_id = dv.user_id
                      AND EXISTS(SELECT 1 FROM cm_energy_account_logs ceal 
                      WHERE ceal.energy_account_id = cea.id 
                      AND ceal.current_status = 4 
                      AND ceal.created_date BETWEEN DATE_SUB(@end_date, INTERVAL 90 DAY) AND @end_date) -- Has 3 New Approved PEA in the Last 90 Days
                    ) >= 3
                ),
                dr.is_canceled = (u.active = 'Canceled')

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
            WHERE mr.is_deleted = 0 AND dr.rank_date = @end_date AND  @end_date BETWEEN mr.start_date AND mr.end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }



	private function saveCareerRank($user_id, $rank_id)
    {
        $sql = "
            INSERT INTO cm_career_ranks (user_id, rank_id, date_achieved) 
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

    public function setCareerTitle()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
                LEFT JOIN(
                    SELECT
                    user_id,
                    MAX(rank_id) AS max_rank
                    FROM cm_daily_ranks cdr
                    JOIN users u ON u.id = cdr.user_id
                    WHERE rank_date <= @end_date
                    GROUP BY user_id) a ON a.user_id = dr.user_id
                SET
                   dr.career_title_id = a.max_rank
                WHERE dr.rank_date = @end_date";


		//Career Title: Highest title achieved in the last 12 months.
		$sql = "
			UPDATE cm_daily_ranks dr
			LEFT JOIN(
				SELECT
				user_id,
				MAX(rank_id) AS max_rank
				FROM cm_daily_ranks cdr
				JOIN users u ON u.id = cdr.user_id
				WHERE rank_date BETWEEN DATE_SUB(@end_date, INTERVAL 1 YEAR) AND @end_date
				GROUP BY user_id) a ON a.user_id = dr.user_id
			SET
			   dr.career_title_id = a.max_rank
			WHERE dr.rank_date = @end_date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function setCanceledRank()
    {
        $sql = "
			UPDATE cm_daily_ranks dr
			JOIN users u ON u.id = dr.user_id
			SET
			   dr.rank_id = 1,
			   dr.paid_as_rank_id = 1
			WHERE dr.rank_date = @end_date AND u.active = 'Canceled'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function initializeVolumes()
    {
        $sql = "
            INSERT INTO cm_daily_volumes (
                user_id, 
                volume_date, 
                pea,
                ta,
				qta,
				mar,
				level,
				pea_flowing
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
                0 pea,
                0 ta,
				0 qta,
				0 mar,
				d.level,
				0 pea_flowing
            FROM downline d
            WHERE EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = d.user_id AND FIND_IN_SET(c.catid, @affiliates))
            ON DUPLICATE KEY UPDATE
                pea = 0,
                ta = 0,
				qta = 0,
				mar = 0,
                level = d.level,
				pea_flowing = 0,
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
                career_title_id, 
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
                1 AS career_title_id, 
                0 AS is_active,
                0 AS is_system_active
            FROM cm_daily_volumes
            WHERE volume_date = @end_date
            ON DUPLICATE KEY UPDATE 
                min_rank_id = 1,
                rank_id = 1,
                paid_as_rank_id = 1,
                career_title_id = 1,
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

    private function deleteAffiliateRecords()
    {
        $sql = "
            DELETE dr FROM cm_daily_ranks dr 
            WHERE dr.rank_date = @end_date
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = dr.user_id AND FIND_IN_SET(cm.catid, @affiliates))
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $sql = "
            DELETE dv FROM cm_daily_volumes dv
            WHERE dv.volume_date = @end_date
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = dv.user_id AND FIND_IN_SET(cm.catid, @affiliates))
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

    public static function getNextRankRequirementsByDailyVolume(DailyVolume $volume, Rank $nextRank)
    {
        if ($nextRank === null || $volume === null) return [];

        $needs = [];

        $user_id = $volume->user_id;
        $next_rank_id = +$nextRank->id >= 6 ? +$nextRank->id - 2 : +$nextRank->id; //minus 2 ranks every leg requirement from rank 6 - 10

        $rank_count = Rank::countLegRequirement($next_rank_id,$user_id);
        $rank_count =  2 - +$rank_count; // leg requirement - leg count

        $rank_count_requirement = Rank::countLegRequirement(8,$user_id); //national leader rank leg requirement in rank 11 and 12
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
/* 
        if (false && "test") {
            $needs[] = [
                'html' => '<h1>HTML TEST</h1>',
            ];
		} */


		 //leg requirements
		if (+$nextRank->id === 6) {
            if($rank_count > 0) {
                $needs[] = [
                    'label' => 'Leg Requirements',
                    'value' => $rank_count . ' Watt 30 or higher',
                    'description' => ''
                ];
            }
		} else if (+$nextRank->id === 7) {
            if($rank_count > 0) {
                $needs[] = [
                    'label' => 'Leg Requirements',
                    'value' => $rank_count . ' Watt 60 or higher',
                    'description' => ''
                ];
            }
		} else if (+$nextRank->id === 8) {
		    if($rank_count > 0) {
                $needs[] = [
                    'label' => 'Leg Requirements',
                    'value' => $rank_count . ' Team Leaders or higher',
                    'description' => ''
                ];
            }
		} else if (+$nextRank->id === 9) {
            if($rank_count > 0) {
                $needs[] = [
                    'label' => 'Leg Requirements',
                    'value' => $rank_count . ' Group Leaders or higher',
                    'description' => ''
                ];
            }
	   } else if (+$nextRank->id === 10) {
            if($rank_count > 0) {
                $needs[] = [
                    'label' => 'Leg Requirements',
                    'value' => $rank_count . ' National Leaders or higher',
                    'description' => ''
                ];
            }
	   } else if (+$nextRank->id === 11) {
            $rank_count_requirement = 1 - +$rank_count_requirement;
            if(($rank_count > 0) || ($rank_count_requirement > 0)) {
                $needs[] = [
                    'label' => 'Leg Requirements',
                    'value' => $rank_count_requirement . ' National Leader or higher And '. $rank_count. ' Global Leaders or higher',
                    'html' => '<strong>'.$rank_count_requirement.'</strong> National Leader or higher <br><strong>'.$rank_count.'</strong>Global Leaders or higher'
                ];
            }

	   } else if (+$nextRank->id === 12) {
            $rank_count_requirement = 2 - +$rank_count_requirement;
            if(($rank_count > 0) || ($rank_count_requirement > 0)) {
                $needs[] = [
                    'label' => 'Leg Requirements',
                    'value' => $rank_count_requirement . ' National Leaders or higher And '. $rank_count. ' President Leaders or higher',
                    'html' => '<strong>'.$rank_count_requirement.'</strong> National Leaders or higher <br><strong>'.$rank_count.'</strong> President Leaders or higher'
                ];
            }
	   }

        return $needs;
    }

}