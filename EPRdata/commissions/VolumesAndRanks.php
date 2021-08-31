<?php


namespace Commissions;

use App\DailyVolume;
use App\Rank;
use Illuminate\Support\Facades\DB;
use PDO;


final class VolumesAndRanks extends Console
{
    const IS_DEBUG = false;
    const ROOT_USER_ID = 3;
    const MIN_ACTIVE_CS = 250;
    const MIN_BUILDER_LEG_QV = 1500;

    const COUNTRY_USA = 'US';
    const COUNTRY_CAN = 'CA';

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
            $this->log("Minimum Active CS: " . static::MIN_ACTIVE_CS);

            $this->log("Initializing Transaction Info");
            $this->initializeTransactionInfo();

            $this->log("Deleting ranks and volumes records of customers");
            $this->deleteCustomerRecords();

            $this->log("Deleting achieved ranks");
            $this->deleteAchievedRanks();

            $this->log("Applying Re-qualification Policy");
            $this->applyRequalificationPolicy();

            $this->log("Getting rank requirements");
            $this->getRankRequirements();

            $this->log('Initializing Volumes');
            $this->initializeVolumes();

            $this->log('Initializing Ranks');
            $this->initializeRanks();

            $this->log("Setting Personal (Purchase) Sales Volume & Customer Sales Volume");
            $this->setPersonalPurchaseSalesAndCustomerSalesVolume();

            $this->log("Setting Downline Sales Volume (Uncapped)");
            $this->setDownlineSalesVolumeUncapped();

            $this->log("Setting Category ID");
            $this->setCategoryID(); // para makita kung nag change og plan

            $this->log("Setting If Member Is Active");
            $this->setIfMemberIsActive();

			/*
            $this->log("Setting Lead Director Achieved Date");
            $this->setLeadDirectorRank();
			*/

            $this->log("Setting Minimum Rank");
            $this->setMinimumRank();

            $this->log("Setting Paid-as Rank");
            $this->setRanks();

            $this->log("Setting If Autoship Is Active");
            $this->setIfAutoshipIsActive();

            $this->log("Deleting Previous Highest Achieved Rank This Month");
            $this->deletePreviousHighestAchievedRanksThisMonth();

            $this->log("Updating Previous Highest Achieved Rank This Month");
            $this->updatePreviousHighestAReachievedRanksThisMonth();

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
                @min_active_cs = :min_active_cs
            ")
            ->execute([
                ':root_user_id' => static::ROOT_USER_ID,
                ':start_date' => $this->getStartDate(),
                ':end_date' => $this->getEndDate(),
                ':customers' => $this->customers,
                ':affiliates' => $this->affiliates,
                ':min_active_cs' => static::MIN_ACTIVE_CS,
            ]);

        if (static::IS_DEBUG) {
            $stmt = $this->db->prepare("
                SELECT
                    @root_user_id,
                    @start_date,
                    @end_date,
                    @affiliates,
                    @customers,
                    @min_active_cs
            ");

            $stmt->execute();

            $this->log_debug($stmt->fetch());
        }
    }

    private function setPersonalPurchaseSalesAndCustomerSalesVolume()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    t.user_id,
                    SUM(COALESCE(t.computed_qv, 0)) As ps
                FROM v_cm_transactions t
                WHERE transaction_date BETWEEN @start_date AND @end_date
                    AND t.`type` = 'product'
                    AND FIND_IN_SET(t.purchaser_catid, @affiliates)
                    -- AND FIND_IN_SET(t.sponsor_catid, @affiliates)
                GROUP BY t.user_id
            ) AS a ON a.user_id = dv.user_id 
            LEFT JOIN (
                SELECT
                    ti.upline_id AS user_id,
                    SUM(COALESCE(t.computed_qv, 0)) AS cs,
                    SUM(IF(t.is_autoship = 1, COALESCE(t.computed_qv, 0), 0)) cs_ps
                FROM v_cm_transactions t
                JOIN cm_transaction_info ti ON ti.transaction_id = t.transaction_id
                WHERE t.transaction_date BETWEEN @start_date AND @end_date
                    AND t.`type` = 'product' 
                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                GROUP BY ti.upline_id
            ) AS c ON c.user_id = dv.user_id
            SET
                dv.ps = COALESCE(a.ps, 0),
                dv.cs = COALESCE(c.cs, 0),
                dv.cs_ps = COALESCE(c.cs_ps, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setDownlineSalesVolumeUncapped()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`, volume) AS (
                    SELECT 
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        u.id AS root_id,
                        0 AS `level`,
                        dv.ps + dv.cs AS volume
                    FROM users u
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = @end_date
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`,
                        dv.ps + dv.cs AS volume
                    FROM users u
                    JOIN downline ON u.sponsorid = downline.user_id
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = @end_date
                )
                SELECT 
                    d.root_id AS user_id,
                    SUM(d.volume) AS ds_uncapped
                FROM downline d
                GROUP BY d.root_id
            ) a ON a.user_id = dv.user_id
            SET
                dv.ds_uncapped = a.ds_uncapped
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function getUsersWithVolumes()
    {
        $sql = "
            SELECT
                dv.user_id,
                a.users_ds_uncapped,
                a.`users`,
                dv.`level`
            FROM cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    u.sponsorid AS user_id,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', dv.user_id, 'ds_uncapped', dv.ds_uncapped)), 
                    ']') `users`,
                    GROUP_CONCAT(dv.ds_uncapped) `users_ds_uncapped`
                FROM cm_daily_volumes dv
                JOIN users u ON u.id = dv.user_id
                WHERE dv.volume_date = @end_date AND dv.ds_uncapped > 0
                GROUP BY u.sponsorid
            ) a ON a.user_id = dv.user_id
            WHERE dv.volume_date = @end_date
            ORDER BY dv.`level` DESC, d.created_date DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function setRanks()
    {
        $end_date = $this->getEndDate();

        $volumes = DailyVolume::with('rank')->date($this->getEndDate())
            ->leftJoin(DB::raw("(
                SELECT
                    u.sponsorid AS sub_user_id,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', dv.user_id, 'ds_uncapped', dv.ds_uncapped)), 
                    ']') `users`,
                    GROUP_CONCAT(dv.ds_uncapped) `users_ds_uncapped`
                FROM cm_daily_volumes dv
                JOIN users u ON u.id = dv.user_id
                WHERE dv.volume_date = '$end_date' AND dv.ds_uncapped > 0
                GROUP BY u.sponsorid
            ) AS a"), function ($join) {
                $join->on("a.sub_user_id", "=", "cm_daily_volumes.user_id");
            })
            ->orderBy('level', 'desc')
            ->orderBy('user_id', 'desc')->get();

        $level = null;

        foreach ($volumes as $volume) {

            if ($level !== $volume->level) {
                $level = $volume->level;
                $this->log("Processing Level $level");
            }

            $user_id = +$volume->user_id;

            $users_ds_uncapped = explode(',', $volume->users_ds_uncapped);
            $users_ds_uncapped[] = +$volume->ps + +$volume->cs;

            $volume->ds = array_sum($users_ds_uncapped);

            $c = $this->getMaxRankPerLegCount($user_id);

            $r = config('commission.ranks');

            $volume->mgr_leg_count = +$c->get($r['manager']);
            $volume->lm_leg_count = +$c->get($r['lead-manager']);
            $volume->sm_leg_count = +$c->get($r['senior-manager']);
            $volume->dir_leg_count = +$c->get($r['director']);
            $volume->ld_leg_count = +$c->get($r['lead-director']);
            $volume->sd_leg_count = +$c->get($r['senior-director']);
            $volume->ed_leg_count = +$c->get($r['executive-director']);
            $volume->led_leg_count = +$c->get($r['lead-executive-director']);
            $volume->sed_leg_count = +$c->get($r['senior-executive-director']);
            $volume->vp_leg_count = +$c->get($r['vice-president']);
            $volume->svp_leg_count = +$c->get($r['senior-vice-president']);

            $volume->builder_leg_count = $this->getBuilderLegCount($user_id);

            if(true || $volume->ds >= 200000) {
                $new_gen_q = $this->getNewGenQ($user_id);
                $volume->new_gen_q_count = +$new_gen_q['count'];
                // $new_gen_q_users = $new_gen_q['users'];
            }

            $rank_id = config('commission.ranks.team-member');

            do {
                $potential_rank_id = $this->getRank($volume);

                if ($potential_rank_id === config('commission.ranks.team-member')) {
                    break;
                }

                $volume->ds = $this->applyMSR($potential_rank_id, $users_ds_uncapped);

                $rank_id = $this->getRank($volume);

            } while ($potential_rank_id != $rank_id);

            $volume->ds_next_rank = $this->applyMSR($rank_id + 1, $users_ds_uncapped);

            $volume->save();

            $rank = $volume->rank;

            $rank->rank_id = $rank_id;
            $rank->paid_as_rank_id = $rank->rank_id > +$rank->min_rank_id ? $rank->rank_id : $rank->min_rank_id;

            $this->saveAchievedRank($user_id, $rank->paid_as_rank_id);
            $this->setTitle($user_id);

            $rank->save();
        }
    }

    private function getRank(DailyVolume $volume)
    {
        foreach ($this->rank_requirements as $rank) {

            $svp_leg_count = +$volume->svp_leg_count;
            $vp_leg_count = $svp_leg_count + +$volume->vp_leg_count;
            $sed_leg_count = $vp_leg_count + +$volume->sed_leg_count;
            $led_leg_count = $sed_leg_count + +$volume->led_leg_count;
            $ed_leg_count = $led_leg_count + +$volume->ed_leg_count;
            $sd_leg_count = $ed_leg_count + +$volume->sd_leg_count;
            $ld_leg_count = $sd_leg_count + +$volume->ld_leg_count;
            $dir_leg_count = $ld_leg_count + +$volume->dir_leg_count;
            $sm_leg_count = $dir_leg_count + +$volume->sm_leg_count;
            $lm_leg_count = $sm_leg_count + +$volume->lm_leg_count;
            $mgr_leg_count = $lm_leg_count + +$volume->mgr_leg_count;

            $svp_leg_count_requirement = +$volume->svp_leg_count_requirement;
            $vp_leg_count_requirement = $svp_leg_count_requirement - +$volume->vp_leg_count_requirement;
            $sed_leg_count_requirement = $vp_leg_count_requirement - +$volume->sed_leg_count_requirement;
            $led_leg_count_requirement = $sed_leg_count_requirement - +$volume->led_leg_count_requirement;
            $ed_leg_count_requirement = $led_leg_count_requirement - +$volume->ed_leg_count_requirement;
            $sd_leg_count_requirement = $ed_leg_count_requirement - +$volume->sd_leg_count_requirement;
            $ld_leg_count_requirement = $sd_leg_count_requirement - +$volume->ld_leg_count_requirement;
            $dir_leg_count_requirement = $ld_leg_count_requirement - +$volume->dir_leg_count_requirement;
            $sm_leg_count_requirement = $dir_leg_count_requirement - +$volume->sm_leg_count_requirement;
            $lm_leg_count_requirement = $sm_leg_count_requirement - +$volume->lm_leg_count_requirement;
            $mgr_leg_count_requirement = $lm_leg_count_requirement - +$volume->mgr_leg_count_requirement;

            if (
                +$volume->cs >= +$rank->cs_requirement
                && +$volume->ds >= +$rank->ds_requirement
                && +$volume->builder_leg_count >= +$rank->builder_leg_count_requirement
                && +$volume->new_gen_q_count >= +$rank->new_gen_q_count_requirement

                && $svp_leg_count >= +$rank->svp_leg_count_requirement
                && ($vp_leg_count - $svp_leg_count_requirement) >= +$rank->vp_leg_count_requirement
                && ($sed_leg_count - $vp_leg_count_requirement) >= +$rank->sed_leg_count_requirement
                && ($led_leg_count - $sed_leg_count_requirement) >= +$rank->led_leg_count_requirement
                && ($ed_leg_count - $led_leg_count_requirement) >= +$rank->ed_leg_count_requirement
                && ($sd_leg_count - $ed_leg_count_requirement) >= +$rank->sd_leg_count_requirement
                && ($ld_leg_count - $sd_leg_count_requirement) >= +$rank->ld_leg_count_requirement
                && ($dir_leg_count - $ld_leg_count_requirement) >= +$rank->dir_leg_count_requirement
                && ($sm_leg_count - $dir_leg_count_requirement) >= +$rank->sm_leg_count_requirement
                && ($lm_leg_count - $sm_leg_count_requirement) >= +$rank->lm_leg_count_requirement
                && ($mgr_leg_count - $lm_leg_count_requirement) >= +$rank->mgr_leg_count_requirement

            ) return +$rank->id;

        }

        return config('commission.ranks.team-member');
    }

    private function applyMSR($rank_id, $users_ds_uncapped)
    {
        if ($rank_id > config('commission.ranks.senior-vice-president')) $rank_id = config('commission.ranks.senior-vice-president');

        if ($rank_id < config('commission.ranks.representative')) $rank_id = config('commission.ranks.representative');

        $msr = +$this->rank_requirements->where('id', $rank_id)->first()->msr;

        $ds = 0;

        foreach ($users_ds_uncapped as $user_ds_uncapped) {

            $ds += ($user_ds_uncapped > $msr) ? $msr : $user_ds_uncapped;
        }

        return $ds;
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
                    MAX(dr.paid_as_rank_id) rank_id,
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

    private function getBuilderLegCount($user_id)
    {
        $min_qv = static::MIN_BUILDER_LEG_QV;

        $sql = "
            SELECT
              COUNT(1) `count`
            FROM cm_daily_volumes dv
            JOIN users u ON u.id = dv.user_id
            WHERE dv.volume_date = @end_date AND dv.ds_uncapped >= $min_qv
                AND u.sponsorid = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return +$stmt->fetchColumn();
    }

    private function getNewGenQ($user_id)
    {
        $senior_manager = config('commission.ranks.senior-manager');

        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`, title_id) AS (
                SELECT 
                    u.id AS user_id,
                    u.sponsorid AS parent_id,
                    1 AS `level`,
                    dr.title_id
                FROM users u
                JOIN cm_daily_volumes dv ON dv.user_id = u.id
                JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
                WHERE dv.volume_date = @end_date AND u.sponsorid = :user_id
                
                UNION ALL
                
                SELECT
                    u.id AS user_id,
                    u.sponsorid AS parent_id,
                    d.`level` + 1 `level`,
                    dr.title_id
                FROM users u
                JOIN downline AS d ON u.sponsorid = d.user_id
                JOIN cm_daily_volumes dv ON dv.user_id = u.id
                JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
                WHERE dv.volume_date = @end_date AND d.title_id < $senior_manager
            )
            SELECT
                COUNT(DISTINCT d.user_id) `count`, 
                GROUP_CONCAT(DISTINCT d.user_id) `users`
            FROM downline d
            JOIN cm_achieved_ranks r ON r.user_id = d.user_id
            WHERE -- r.date_achieved BETWEEN cm_first_day(DATE_SUB(@end_date, INTERVAL 11 MONTH)) AND @end_date
                r.reachieved_date BETWEEN cm_first_day(DATE_SUB(@end_date, INTERVAL 11 MONTH)) AND @end_date
                AND r.rank_id >= $senior_manager
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$result) {
            return [
                'count' => 0,
                'users' => null
            ];
        }

        return $result;
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

    private function setTitle($user_id)
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            LEFT JOIN cm_achieved_ranks ar ON ar.user_id = dr.user_id
                AND ar.rank_id = (
                    SELECT _ar.rank_id
                    FROM cm_achieved_ranks _ar
                    WHERE _ar.user_id = ar.user_id
                        AND _ar.reachieved_date IS NOT NULL 
                        AND _ar.reachieved_date <= @end_date
                    ORDER BY _ar.rank_id DESC
                    LIMIT 1
            )
            SET dr.title_id = IFNULL(ar.rank_id, 1)
            WHERE dr.rank_date = @end_date AND dr.user_id = $user_id
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setLeadDirectorRank()
    {
        $lead_director = config('commission.ranks.lead-director');
        $sql = "UPDATE cm_daily_ranks cdr
                LEFT JOIN (
                    SELECT 
                        dr.user_id,
                        dr.rank_date AS achieved_date,
                        LAST_DAY(DATE_ADD(dr.rank_date, INTERVAL 12 MONTH)) valid_until_date,
                        IF(LAST_DAY(DATE_ADD(dr.rank_date, INTERVAL 12 MONTH)) >= @end_date, 1, 0) is_lead_director_once
                    FROM cm_daily_ranks dr
                    JOIN users u ON u.id = dr.user_id
                    WHERE dr.rank_date = (
							SELECT _la.rank_date
							FROM cm_daily_ranks _la
							WHERE _la.user_id = dr.user_id
							AND _la.paid_as_rank_id = dr.paid_as_rank_id
							AND _la.rank_date = LAST_DAY(_la.rank_date)
							AND _la.rank_date <= @end_date
							ORDER BY _la.rank_date DESC 
							LIMIT 1
					) AND dr.paid_as_rank_id = (
						SELECT 
						cdr.paid_as_rank_id 
						FROM
						cm_daily_ranks cdr 
						WHERE cdr.user_id = dr.user_id 
						AND cdr.paid_as_rank_id >= $lead_director 
						AND cdr.rank_date <= @end_date 
						ORDER BY dr.paid_as_rank_id DESC 
						LIMIT 1)
                ) AS a ON a.user_id = cdr.user_id
                SET 
                    cdr.is_lead_director_once = COALESCE(a.is_lead_director_once, 0),
                    cdr.lead_director_achieved_date = IF(a.is_lead_director_once, a.achieved_date, NULL)
                WHERE cdr.rank_date = @end_date";

		$sql = "UPDATE cm_daily_ranks cdr
				LEFT JOIN (
					
					
				)
				SET 
					cdr.is_lead_director_once = 1,
					cdr.lead_director_achieved_date = '2021-01-01'
				WHERE cdr.rank_date = @end_date";

        $smt = $this->db->prepare($sql);
        $smt->execute();

    }

    private function applyRequalificationPolicy()
    {
        $senior_manager = config('commission.ranks.senior-manager');

        $sql = "
            UPDATE cm_achieved_ranks ar
            JOIN (
                SELECT
                    a.user_id,
                    a.reachieved_date,
                    a.recent_achieved_date,
                    a.valid_until_date,
                    a.is_maintained,
                    a.highest_rank_id,
                    IFNULL(dr.paid_as_rank_id, $senior_manager) lowest_rank_id
                FROM (
                    SELECT
                        ar.user_id,
                        ar.rank_id AS highest_rank_id,
                        ar.reachieved_date,
                        la.rank_date AS recent_achieved_date,
                        LAST_DAY(DATE_ADD(la.rank_date, INTERVAL 12 MONTH)) valid_until_date,
                        IF(LAST_DAY(DATE_ADD(la.rank_date, INTERVAL 12 MONTH)) >= @end_date, 1, 0) is_maintained
                    FROM cm_achieved_ranks ar
                    JOIN users u ON u.id = ar.user_id
                    JOIN cm_daily_ranks la ON la.user_id = ar.user_id AND la.paid_as_rank_id = ar.rank_id
                        AND la.rank_date = (
                            SELECT _la.rank_date
                            FROM cm_daily_ranks _la
                            WHERE _la.user_id = la.user_id
                                AND _la.paid_as_rank_id = la.paid_as_rank_id
                                AND _la.rank_date = LAST_DAY(_la.rank_date)
                                AND _la.rank_date <= @end_date
                            ORDER BY _la.rank_date DESC 
                            LIMIT 1
                        )
                    WHERE ar.rank_id = (SELECT _ar.rank_id FROM cm_achieved_ranks _ar WHERE _ar.user_id = ar.user_id AND _ar.rank_id >= $senior_manager AND _ar.reachieved_date <= @end_date ORDER BY _ar.rank_id DESC LIMIT 1)
                        AND ar.reachieved_date IS NOT NULL
                ) a
                LEFT JOIN cm_daily_ranks dr ON dr.user_id = a.user_id AND dr.rank_date = a.valid_until_date
                WHERE a.is_maintained = 0
            ) a ON a.user_id = ar.user_id AND ar.rank_id BETWEEN a.lowest_rank_id AND a.highest_rank_id
            SET ar.reachieved_date = NULL
            WHERE a.user_id = ar.user_id AND ar.rank_id BETWEEN a.lowest_rank_id AND a.highest_rank_id
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
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

    private function updatePreviousHighestAReachievedRanksThisMonth()
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
                dr.is_active =  dv.cs >= @min_active_cs,
                dr.is_system_active = IF(dr.cat_id = 8033, 0, IF(u.active='Yes', 1, 0))
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
            SET dr.min_rank_id = mr.rank_id, dr.is_active = mr.rank_id > 1
            WHERE mr.is_deleted = 0 AND dr.rank_date = @end_date AND  @end_date BETWEEN mr.start_date AND mr.end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setIfAutoshipIsActive()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            LEFT JOIN oc_autoship oa ON oa.customer_id = dr.user_id
            SET
                dr.is_autoship_active = IF(oa.is_active IS NULL OR oa.is_active = 0, 0, 1)
            WHERE dr.rank_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
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
                
                ps,
                cs,
                ds_uncapped,
                ds,
                ds_next_rank,
                cs_ps,
                builder_leg_count,
                mgr_leg_count,
                lm_leg_count,
                sm_leg_count,
                dir_leg_count,
                ld_leg_count,
                sd_leg_count,
                ed_leg_count,
                led_leg_count,
                sed_leg_count,
                vp_leg_count,
                new_gen_q_count,
                
                level,
                sponsor_id
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
                
                0 ps,
                0 cs,
                0 ds_uncapped,
                0 ds,
                0 ds_next_rank,
                0 cs_ps,
                0 builder_leg_count,
                0 mgr_leg_count,
                0 lm_leg_count,
                0 sm_leg_count,
                0 dir_leg_count,
                0 ld_leg_count,
                0 sd_leg_count,
                0 ed_leg_count,
                0 led_leg_count,
                0 sed_leg_count,
                0 vp_leg_count,
                0 new_gen_q_count,
                
                d.level,
                d.parent_id
            FROM downline d
            JOIN cm_affiliates a ON a.user_id = d.user_id
            WHERE EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = d.user_id AND FIND_IN_SET(c.catid, @affiliates))
                AND a.affiliated_date <= @end_date
            ON DUPLICATE KEY UPDATE
                ps = 0,
                cs = 0,
                ds_uncapped = 0,
                ds = 0,
                ds_next_rank = 0,
                cs_ps = 0,
                builder_leg_count = 0,
                mgr_leg_count = 0,
                lm_leg_count = 0,
                sm_leg_count = 0,
                dir_leg_count = 0,
                ld_leg_count = 0,
                sd_leg_count = 0,
                ed_leg_count = 0,
                led_leg_count = 0,
                sed_leg_count = 0,
                vp_leg_count = 0,
                new_gen_q_count = 0,
                
                level = d.level,
                sponsor_id = IF(@end_date = CURRENT_DATE(), VALUES(sponsor_id), sponsor_id),
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
                title_id,
                is_lead_director_once,
                lead_director_achieved_date,
                is_autoship_active,
                cat_id
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
                1 AS title_id,
                0 AS is_lead_director_once,
                NULL AS lead_director_achieved_date,
                0 AS is_autoship_active,
                NULL AS cat_id
            FROM cm_daily_volumes dv
            WHERE volume_date = @end_date
            ON DUPLICATE KEY UPDATE 
                min_rank_id = 1,
                rank_id = 1,
                paid_as_rank_id = 1,
                is_active = 0,
                is_system_active = 0,
                volume_id = VALUES(volume_id),
                title_id = 1,
                is_lead_director_once = 0,
                lead_director_achieved_date = NULL,
                is_autoship_active = 0,
                cat_id = NULL,
                updated_at = CURRENT_TIMESTAMP();
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function initializeTransactionInfo()
    {
        $sql = "
            INSERT INTO cm_transaction_info (
                transaction_id,
                purchaser_id,
                sponsor_id,
                upline_id,
                purchaser_catid,
                sponsor_catid
            )
            SELECT
                t.transaction_id,
                t.user_id,
                t.sponsor_id,
                IF(FIND_IN_SET(t.sponsor_catid, @affiliates), t.sponsor_id, getFirstUplineAffiliateByDate(t.user_id, t.transaction_date)),
                t.purchaser_catid,
                t.sponsor_catid
            FROM v_cm_transactions t
            WHERE t.transaction_date BETWEEN @start_date AND @end_date
                AND t.`type` = 'product'
            ON DUPLICATE KEY UPDATE 
                purchaser_id = VALUES(purchaser_id),
                sponsor_id = VALUES(sponsor_id),
                upline_id = VALUES(upline_id),
                purchaser_catid = VALUES(purchaser_catid),
                sponsor_catid = VALUES(sponsor_catid);
        
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    public static function getNextRankRequirementsByDailyVolume(DailyVolume $volume, Rank $next_rank)
    {
        if ($next_rank === null || $volume === null) return [];

        $needs = [];

        $svp_leg_count = +$volume->svp_leg_count;
        $vp_leg_count = $svp_leg_count + +$volume->vp_leg_count;
        $sed_leg_count = $vp_leg_count + +$volume->sed_leg_count;
        $led_leg_count = $sed_leg_count + +$volume->led_leg_count;
        $ed_leg_count = $led_leg_count + +$volume->ed_leg_count;
        $sd_leg_count = $ed_leg_count + +$volume->sd_leg_count;
        $ld_leg_count = $sd_leg_count + +$volume->ld_leg_count;
        $dir_leg_count = $ld_leg_count + +$volume->dir_leg_count;
        $sm_leg_count = $dir_leg_count + +$volume->sm_leg_count;
        $lm_leg_count = $sm_leg_count + +$volume->lm_leg_count;
        $mgr_leg_count = $lm_leg_count + +$volume->mgr_leg_count;
        $new_gen_q_count = +$volume->new_gen_q_count;

        $svp_leg_count_requirement = +$next_rank->svp_leg_count_requirement;
        $vp_leg_count_requirement = +$next_rank->svp_leg_count_requirement - +$volume->vp_leg_count_requirement;
        $sed_leg_count_requirement = +$next_rank->vp_leg_count_requirement - +$volume->sed_leg_count_requirement;
        $led_leg_count_requirement = +$next_rank->sed_leg_count_requirement - +$volume->led_leg_count_requirement;
        $ed_leg_count_requirement = +$next_rank->led_leg_count_requirement - +$volume->ed_leg_count_requirement;
        $sd_leg_count_requirement = +$next_rank->ed_leg_count_requirement - +$volume->sd_leg_count_requirement;
        $ld_leg_count_requirement = +$next_rank->sd_leg_count_requirement - +$volume->ld_leg_count_requirement;
        $dir_leg_count_requirement = +$next_rank->ld_leg_count_requirement - +$volume->dir_leg_count_requirement;
        $sm_leg_count_requirement = +$next_rank->dir_leg_count_requirement - +$volume->sm_leg_count_requirement;
        $lm_leg_count_requirement = +$next_rank->sm_leg_count_requirement - +$volume->lm_leg_count_requirement;
        $mgr_leg_count_requirement = +$next_rank->lm_leg_count_requirement - +$volume->mgr_leg_count_requirement;

        $cs_requirement = $next_rank->cs_requirement - +$volume->cs;
        $ds_requirement = $next_rank->ds_requirement - +$volume->ds_next_rank;
        $builder_leg_count_requirement = $next_rank->builder_leg_count_requirement - +$volume->builder_leg_count;


        $next_svp_leg_count = $next_rank->svp_leg_count_requirement - $svp_leg_count;
        $next_vp_leg_count = $next_rank->vp_leg_count_requirement - $vp_leg_count - $svp_leg_count_requirement;
        $next_sed_leg_count = $next_rank->sed_leg_count_requirement - $sed_leg_count - $vp_leg_count_requirement;
        $next_led_leg_count = $next_rank->led_leg_count_requirement - $led_leg_count - $sed_leg_count_requirement;
        $next_ed_leg_count = $next_rank->ed_leg_count_requirement - $ed_leg_count - $led_leg_count_requirement;
        $next_sd_leg_count = $next_rank->sd_leg_count_requirement - $sd_leg_count - $ed_leg_count_requirement;
        $next_ld_leg_count = $next_rank->ld_leg_count_requirement - $ld_leg_count - $sd_leg_count_requirement;
        $next_dir_leg_count = $next_rank->dir_leg_count_requirement - $dir_leg_count - $ld_leg_count_requirement;
        $next_sm_leg_ccount = $next_rank->sm_leg_count_requirement - $sm_leg_count - $dir_leg_count_requirement;
        $next_lm_leg_count = $next_rank->lm_leg_count_requirement - $lm_leg_count - $sm_leg_count_requirement;
        $next_mgr_leg_count = $next_rank->mgr_leg_count_requirement - $mgr_leg_count - $lm_leg_count_requirement;
        $new_gen_q_leg_count = $next_rank->new_gen_q_count_requirement - $new_gen_q_count;


        if($new_gen_q_leg_count > 0) {
            $needs[] = [
              'value' => $new_gen_q_leg_count,
              'description' => 'NewGenQ'
            ];
        }

        if($next_svp_leg_count > 0 ) {
            $needs[] = [
              'value' => $next_svp_leg_count,
              'description' => 'Senior VP Leg(s)'
            ];
        }

        if($next_vp_leg_count > 0) {
            $needs[] = [
                'value' => $next_vp_leg_count,
                'description' => 'Vice President Leg(s)'
            ];
        }

        if($next_sed_leg_count > 0) {
            $needs[] = [
                'value' => $next_sed_leg_count,
                'description' => 'Senior Executive Director Leg(s)'
            ];
        }

        if($next_led_leg_count > 0) {
            $needs[] = [
                'value' => $next_led_leg_count,
                'description' => 'Lead Executive Director Leg(s)'
            ];
        }

        if($next_ed_leg_count > 0) {
            $needs[] = [
                'value' => $next_ed_leg_count,
                'description' => 'Executive Director Leg(s)'
            ];
        }

        if($next_sd_leg_count > 0) {
            $needs[] = [
                'value' => $next_sd_leg_count,
                'description' => 'Senior Director Leg(s)'
            ];
        }

        if($next_ld_leg_count > 0) {
            $needs[] = [
                'value' => $next_ld_leg_count,
                'description' => 'Lead Director Leg(s)'
            ];
        }

        if($next_dir_leg_count > 0) {
            $needs[] = [
                'value' => $next_dir_leg_count,
                'description' => 'Director Leg(s)'
            ];
        }

        if($next_sm_leg_ccount > 0) {
            $needs[] = [
                'value' => $next_sm_leg_ccount,
                'description' => 'Senior Manager Leg(s)'
            ];
        }

        if($next_lm_leg_count > 0) {
            $needs[] = [
                'value' => $next_lm_leg_count,
                'description' => 'Lead Manager Leg(s)'
            ];
        }

        if($next_mgr_leg_count > 0) {
            $needs[] = [
                'value' => $next_mgr_leg_count,
                'description' => 'Manager Leg(s)'
            ];
        }

        if($builder_leg_count_requirement > 0) {
            $needs[] = [
                'value' => $builder_leg_count_requirement,
                'description' => 'Builder Leg(s)',
            ];
        }

        if ($cs_requirement > 0) {
            $needs[] = [
                'value' => $cs_requirement,
                'description' => 'Customer Sales',
            ];
        }

        if ($ds_requirement > 0) {
            $needs[] = [
                'value' => $ds_requirement,
                'description' => 'Downline Sales',
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