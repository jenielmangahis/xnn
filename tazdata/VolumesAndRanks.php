<?php

namespace Commissions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB as DB;
use App\DailyVolume;
use \PDO;
use DateTime;

final class VolumesAndRanks
{
    const MINIMUM_PSC_REQUIREMENT = 300; // 300

    protected $db;
    protected $end_date;
    protected $start_date;
    protected $affiliates;
    protected $customers;
    protected $root_user_id;
    protected $enrollment_kits;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->affiliates = config('commission.member-types.affiliates');
        $this->customers = config('commission.member-types.customers');
        $this->enrollment_kits = config('commission.products.enrollment-kits');
        $this->root_user_id = 3;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function run($end_date = null)
    {
        if ($end_date === 'now') {

            $end_date = Carbon::now();

        } elseif ($end_date != null) {

            if (!DateTime::createFromFormat('Y-m-d', $end_date)) throw new \Exception("Invalid date");

            $end_date = Carbon::createFromFormat('Y-m-d', $end_date);

        } else {

            $end_date = Carbon::yesterday();

        }

        $this->end_date = $end_date->format("Y-m-d");
        $this->start_date = $end_date->copy()->firstOfMonth()->format("Y-m-d");

        $this->process();
    }

    private function process()
    {
        DB::transaction(function () {

            $this->setMainParameters();

            $this->log("Start Date: " . $this->start_date);
            $this->log("End Date: " . $this->end_date);

            $this->log("Customer IDs: " . $this->customers);
            $this->log("Affiliate IDs: " . $this->affiliates);

            $this->log("Enrollment Kit IDs: " . $this->enrollment_kits);

            $this->log("Deleting ranks and volumes records of customers");
            $this->deleteCustomerRecords();

            $this->log("Deleting achieved ranks. ");
            $this->deleteAchievedRank();

            $this->log("Deleting career ranks. ");
            $this->deleteCareerRank();

            $this->log('Initializing Volumes');
            $this->initializeVolumes();

            $this->log('Initializing Ranks');
            $this->initializeRanks();

            $this->log('Setting Personal Sales (Uncapped)');
            $this->setPersonalSalesUncapped();

            $this->log('Setting Personal Sales (Capped)');
            $this->setPersonalSalesCapped();

            $this->log('Setting Family Sales');
            $this->setFamilySales();

            $this->log('Setting Downline Sales (Uncapped)');
            $this->setDownlineSalesUncapped();

            $this->log("Setting If Member Is Active");
            $this->setIfMemberIsActive();

            $this->log('Setting Minimum Ranks');
            $this->setMinimumRank();

            $this->log("Setting Personal BBA Count");
            $this->setPersonalBBACount();

            $this->log("Setting Downline Sales (Capped) and Paid-as Rank");
            $this->setRanks();

            $this->log("Deleting Previous Highest Achieved Rank This Month");
            $this->deletePreviousHighestAchievedRanksThisMonth();

            $this->log("Deleting Previous Highest Career Rank This Month");
            $this->deletePreviousHighestCareerRanksThisMonth();

        }, 3);
    }

    private function setPersonalSalesUncapped()
    {
        // TODO: add compression for customer
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    t.user_id,
                    SUM(COALESCE(computed_qs, 0)) As ppv
                FROM v_cm_transactions t
                WHERE DATE(transaction_date) BETWEEN DATE(cm_first_day(@end_date)) AND DATE(@end_date)
                    AND t.`type` = 'product'
                    AND FIND_IN_SET(t.purchaser_catid, @affiliates)
                GROUP BY t.user_id        
            ) As a ON a.user_id = dv.user_id 
            LEFT JOIN (
                WITH RECURSIVE downline (user_id, parent_id, `level`, root_id) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`,
                        sponsorid root_id
                    FROM users u
                    WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @customers))
                        OR EXISTS(
                            SELECT 1 
                            FROM v_cm_transactions t 
                            WHERE t.`type` = 'product' 
                                AND DATE(t.transaction_date) BETWEEN DATE(cm_first_day(@end_date)) AND DATE(@end_date)
                                AND FIND_IN_SET(t.purchaser_catid, @customers)
                                AND t.user_id = u.id 
                        )
                        
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`,
                        downline.root_id
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.id AND FIND_IN_SET(cm.catid, @customers))
                        OR EXISTS(
                            SELECT 1 
                            FROM v_cm_transactions t 
                            WHERE t.`type` = 'product' 
                                AND DATE(t.transaction_date) BETWEEN DATE(cm_first_day(@end_date)) AND DATE(@end_date)
                                AND FIND_IN_SET(t.purchaser_catid, @customers)
                                AND t.user_id = p.id 
                        )
                    
                )
                SELECT
                    d.root_id AS user_id,
                    SUM(COALESCE(t.computed_qs, 0)) AS pcv
                FROM downline d
                JOIN v_cm_transactions t ON t.user_id = d.user_id
                WHERE DATE(t.transaction_date) BETWEEN DATE(cm_first_day(@end_date)) AND DATE(@end_date)
                    AND t.`type` = 'product' 
                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                GROUP BY d.root_id    
            ) As c ON c.user_id = dv.user_id
            SET
                dv.ppv = COALESCE(a.ppv, 0),
                dv.pcv = COALESCE(c.pcv, 0),
                dv.ps = COALESCE(a.ppv, 0) + COALESCE(c.pcv, 0, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setPersonalSalesCapped()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    a.user_id,
                    SUM(a.ppv) AS ppv
                FROM (
                    SELECT    
                        t.user_id,
                        SUM(COALESCE(computed_qs, 0)) As ppv
                    FROM v_cm_transactions t
                    WHERE DATE(transaction_date) BETWEEN DATE(cm_first_day(@end_date)) AND DATE(@end_date)
                        AND t.`type` = 'product' 
                        AND FIND_IN_SET(t.purchaser_catid, @affiliates)
                        AND FIND_IN_SET(IFNULL(t.item_id, 0), @enrollment_kits)
                    GROUP BY t.user_id
                    
                    UNION ALL
                    
                    SELECT 
                        t.user_id,
                        IF(SUM(COALESCE(computed_qs, 0)) > 100, 100, SUM(COALESCE(computed_qs, 0))) As ppv
                    FROM v_cm_transactions t
                    WHERE DATE(transaction_date) BETWEEN DATE(cm_first_day(@end_date)) AND DATE(@end_date)
                        AND t.`type` = 'product' 
                        AND FIND_IN_SET(t.purchaser_catid, @affiliates)
                        AND NOT FIND_IN_SET(IFNULL(t.item_id, 0), @enrollment_kits)
                    GROUP BY t.user_id
                ) a
                GROUP BY a.user_id
            ) w ON w.user_id = dv.user_id
            SET
                dv.ppv_capped = IFNULL(w.ppv, 0),
                dv.pcv_capped = dv.pcv, -- walay capped ang customer purchases
                dv.ps_capped = IFNULL(w.ppv, 0) + dv.pcv
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setFamilySales()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        id AS root_id,
                        0 AS `level`
                    FROM users u
                    WHERE levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`
                    FROM users u
                    INNER JOIN downline ON u.sponsorid = downline.user_id
                    WHERE u.levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                        AND downline.`level` < 3
                )
                SELECT 
                    d.root_id AS user_id,
                    SUM(dv.ps) AS fs
                FROM downline d
                JOIN cm_daily_volumes dv ON dv.user_id = d.user_id AND dv.volume_date = @end_date
                GROUP BY d.root_id
            ) a ON a.user_id = dv.user_id
            SET
                dv.fs = a.fs
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function setDownlineSalesUncapped()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        id AS root_id,
                        0 AS `level`
                    FROM users u
                    WHERE levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`
                    FROM users u
                    INNER JOIN downline ON u.sponsorid = downline.user_id
                    WHERE u.levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                )
                SELECT 
                    d.root_id AS user_id,
                    SUM(dv.ps) AS ds
                FROM downline d
                JOIN cm_daily_volumes dv ON dv.user_id = d.user_id AND dv.volume_date = @end_date
                GROUP BY d.root_id
            ) a ON a.user_id = dv.user_id
            SET
                dv.ds = a.ds
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function setIfMemberIsActive()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN cm_daily_volumes dv ON dv.id = dr.volume_id
            SET
                dr.is_active = 1
            WHERE dr.rank_date = @end_date
                AND dv.ps_capped >= @minimum_psc_requirement
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function setPersonalBBACount()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            JOIN (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`, active, compress_level) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        id AS root_id,
                        0 AS `level`,
                        u.active,
                        0 compress_level
                    FROM users u
                    WHERE levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`,
                        u.active,
                        downline.compress_level + IF(u.active = 'Yes', 1, 0)
                    FROM users u
                    INNER JOIN downline ON u.sponsorid = downline.user_id
                    WHERE u.levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                        AND downline.compress_level < 1
                )
                SELECT 
                    d.root_id AS user_id,
                    GROUP_CONCAT(d.user_id) `users`,
                    COUNT(d.user_id) `count`
                FROM downline d
                JOIN cm_daily_volumes dv ON dv.user_id = d.user_id AND dv.volume_date = @end_date
                JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
                WHERE d.active = 'Yes'
                    AND d.user_id <> d.root_id
                    AND (dv.ps_capped >= @minimum_psc_requirement OR dr.min_rank_id >= 2)
                    /*
                        NOTE: Dili pa man ta maka evalute ug rank kay need nato icount ang Personal BBA, 
                        so ang gamiton nato kay ang PSc para ma identify tong mga Personal BBA (or higher) sa member.
                        300 PSc ang minimum requirement para sa BBA na rank.
                        Pwede pod nato gamiton ang is_active na column sa cm_daily_ranks.
                        
                        NOTE: ang paid as kay current month, dili previous month
                    */
                GROUP BY d.root_id
            ) a ON a.user_id = dv.user_id
            SET
                dv.personal_bba_users = a.`users`,
                dv.personal_bba_count = a.`count`
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function setMinimumRank()
    {
        $sql = "
            UPDATE cm_daily_ranks dr
            JOIN cm_minimum_ranks mr ON mr.user_id = dr.user_id
            SET dr.min_rank_id = mr.rank_id
            WHERE mr.is_deleted = 0 AND dr.rank_date =  @end_date AND  @end_date BETWEEN mr.start_date AND mr.end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setRanks()
    {
        $users = $this->getUsersWithVolumes();

        $level = null;

        foreach ($users as $user) {

            if ($level !== $user['level']) {
                $level = $user['level'];
                $this->log("Processing Level $level");
            }

            $user_id = +$user['user_id'];
            $ps_capped = +$user['ps_capped'];
            $fs = +$user['fs'];
            $personal_bba_count = +$user['personal_bba_count'];
            $ds_capped_users = $user['users'];

            $users_ds = explode(',', $user['users_ds']);
            $users_ds[] = $user['ps'];

            $ds_capped = array_sum($users_ds);

            $c = $this->getMaxRankPerLegCount($user_id);

            $qualified_leg_r1_count = +$c->get(1);
            $qualified_leg_r2_count = +$c->get(2);
            $qualified_leg_r3_count = +$c->get(3);
            $qualified_leg_r4_count = +$c->get(4);
            $qualified_leg_r5_count = +$c->get(5);
            $qualified_leg_r6_count = +$c->get(6);
            $qualified_leg_r7_count = +$c->get(7);
            $qualified_leg_r8_count = +$c->get(8);
            $qualified_leg_r9_count = +$c->get(9);
            $qualified_leg_r10_count = +$c->get(10);
            $qualified_leg_r11_count = +$c->get(11);
            $qualified_leg_r12_count = +$c->get(12);
            $qualified_leg_r13_count = +$c->get(13);

            $new_gen_q_count = 0;
            $new_gen_q_users = null;

            if($ds_capped >= 100000) { // if maka abot sya sa minimum DS requirement sa BE and up, i get nato iyang NewGenQ
                $new_gen_q = $this->getNewGenQ($user_id);
                $new_gen_q_count = +$new_gen_q['count'];
                $new_gen_q_users = $new_gen_q['users'];
            }

            $rank_id = 1;

            do {
                $potential_rank_id = $this->getRank(
                    $ps_capped,
                    $fs,
                    $ds_capped,
                    $personal_bba_count, $qualified_leg_r1_count, $qualified_leg_r2_count, $qualified_leg_r3_count, $qualified_leg_r4_count, $qualified_leg_r5_count, $qualified_leg_r6_count,
                    $qualified_leg_r7_count, $qualified_leg_r8_count, $qualified_leg_r9_count, $qualified_leg_r10_count, $qualified_leg_r11_count, $qualified_leg_r12_count, $qualified_leg_r13_count,
                    $new_gen_q_count
                );

                if ($potential_rank_id === 1) {
                    break;
                }

                $ds_capped = $this->applyMSR($potential_rank_id, $users_ds);

                $rank_id = $this->getRank(
                    $ps_capped,
                    $fs,
                    $ds_capped,
                    $personal_bba_count, $qualified_leg_r1_count, $qualified_leg_r2_count, $qualified_leg_r3_count, $qualified_leg_r4_count, $qualified_leg_r5_count, $qualified_leg_r6_count,
                    $qualified_leg_r7_count, $qualified_leg_r8_count, $qualified_leg_r9_count, $qualified_leg_r10_count, $qualified_leg_r11_count, $qualified_leg_r12_count, $qualified_leg_r13_count,
                    $new_gen_q_count
                );

            } while ($potential_rank_id != $rank_id);

            $next_ds_capped = $this->applyMSR($rank_id + 1, $users_ds);

            $volume = DailyVolume::date($this->getEndDate())->ofMember($user_id)->firstOrFail();
            $volume->ds_capped = $ds_capped;
            $volume->qualified_leg_r1_count = $qualified_leg_r1_count;
            $volume->qualified_leg_r2_count = $qualified_leg_r2_count;
            $volume->qualified_leg_r3_count = $qualified_leg_r3_count;
            $volume->qualified_leg_r4_count = $qualified_leg_r4_count;
            $volume->qualified_leg_r5_count = $qualified_leg_r5_count;
            $volume->qualified_leg_r6_count = $qualified_leg_r6_count;
            $volume->qualified_leg_r7_count = $qualified_leg_r7_count;
            $volume->qualified_leg_r8_count = $qualified_leg_r8_count;
            $volume->qualified_leg_r9_count = $qualified_leg_r9_count;
            $volume->qualified_leg_r10_count = $qualified_leg_r10_count;
            $volume->qualified_leg_r11_count = $qualified_leg_r11_count;
            $volume->qualified_leg_r12_count = $qualified_leg_r12_count;
            $volume->qualified_leg_r13_count = $qualified_leg_r13_count;
            $volume->next_ds_capped = $next_ds_capped;
            $volume->ds_capped_users = $ds_capped_users;
            $volume->new_gen_q_count = $new_gen_q_count;
            $volume->new_gen_q_users = $new_gen_q_users;

            $volume->save();

            $rank = $volume->rank;
            $rank->rank_id = $rank_id;
            $rank->paid_as_rank_id = ($rank_id > +$rank->min_rank_id ? $rank_id : +$rank->min_rank_id); // calendar month

            $rank->save();

            $this->saveAchievedRank($user_id, $rank_id);
            $this->saveCareerRank($user_id, $rank->paid_as_rank_id);
        }

    }

    private function getNewGenQ($user_id)
    {
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                SELECT 
                    id AS user_id,
                    sponsorid AS parent_id,
                    1 AS `level`
                FROM users
                WHERE sponsorid = :user_id AND levelid = 3
                
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
                COUNT(DISTINCT d.user_id) `count`, 
                GROUP_CONCAT(DISTINCT d.user_id) `users`
            FROM downline d
            JOIN cm_achieved_ranks r ON r.user_id = d.user_id
            WHERE r.date_achieved BETWEEN cm_first_day(DATE_SUB(@end_date, INTERVAL 11 MONTH)) AND @end_date
                AND r.rank_id >= 7 -- GL
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
            INSERT INTO cm_achieved_ranks (user_id, rank_id, date_achieved) 
            VALUES (:user_id, :rank_id, @end_date)
            ON DUPLICATE KEY UPDATE
                date_achieved = IF(date_achieved < VALUES(date_achieved), date_achieved, VALUES(date_achieved))

        ";

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

    private function saveCareerRank($user_id, $rank_id)
    {
        $sql = "
            INSERT INTO cm_career_ranks (user_id, rank_id, date_achieved) 
            SELECT user_id, rank_id, start_date FROM cm_minimum_ranks WHERE is_deleted = 0
            ON DUPLICATE KEY UPDATE
                date_achieved = IF(date_achieved < VALUES(date_achieved), date_achieved, VALUES(date_achieved))

        ";

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

    private function applyMSR($rank_id, $users_ds)
    {
        $msr = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 6000,
            9 => 12000,
            10 => 25000,
            11 => 50000,
            12 => 110000,
            13 => 250000,
        ];

        if ($rank_id > 13) $rank_id = 13;

        if ($rank_id < 8) $rank_id = 8;

        $ds_capped = 0;

        foreach ($users_ds as $user_ds) {
            $m = $msr[$rank_id];
            if ($user_ds > $m) {
                $ds_capped += $m;
            } else {
                $ds_capped += $user_ds;
            }
        }

        return $ds_capped;
    }

    private function getRank(
        $ps_capped,
        $fs,
        $ds_capped,
        $personal_bba_count,
        $qualified_leg_r1_count,
        $qualified_leg_r2_count,
        $qualified_leg_r3_count,
        $qualified_leg_r4_count,
        $qualified_leg_r5_count,
        $qualified_leg_r6_count,
        $qualified_leg_r7_count,
        $qualified_leg_r8_count,
        $qualified_leg_r9_count,
        $qualified_leg_r10_count,
        $qualified_leg_r11_count,
        $qualified_leg_r12_count,
        $qualified_leg_r13_count,
        $new_gen_q_count
    )
    {
        $leg_r13 = $qualified_leg_r13_count;
        $leg_r12 = $leg_r13 + $qualified_leg_r12_count;
        $leg_r11 = $leg_r12 + $qualified_leg_r11_count;
        $leg_r10 = $leg_r11 + $qualified_leg_r10_count;
        $leg_r9 = $leg_r10 + $qualified_leg_r9_count;
        $leg_r8 = $leg_r9 + $qualified_leg_r8_count;
        $leg_r7 = $leg_r8 + $qualified_leg_r7_count;
        $leg_r6 = $leg_r7 + $qualified_leg_r6_count;
        $leg_r5 = $leg_r6 + $qualified_leg_r5_count;
        $leg_r4 = $leg_r5 + $qualified_leg_r4_count;
        $leg_r3 = $leg_r4 + $qualified_leg_r3_count;
        $leg_r2 = $leg_r3 + $qualified_leg_r2_count;
        $leg_r1 = $leg_r2 + $qualified_leg_r1_count;

        if ($ps_capped >= 700 && $fs >= 7000 && $ds_capped >= 500000 && $personal_bba_count >= 4 && $leg_r10 >= 3 && $new_gen_q_count >= 1) return 13;
        elseif ($ps_capped >= 700 && $fs >= 7000 && $ds_capped >= 220000 && $personal_bba_count >= 4 && $leg_r9 >= 3 && $new_gen_q_count >= 1) return 12;
        elseif ($ps_capped >= 700 && $fs >= 7000 && $ds_capped >= 100000 && $personal_bba_count >= 4 && $leg_r8 >= 3 && $new_gen_q_count >= 1) return 11;
        elseif ($ps_capped >= 700 && $fs >= 7000 && $ds_capped >= 50000 && $personal_bba_count >= 4 && $leg_r7 >= 3) return 10;
        elseif ($ps_capped >= 700 && $fs >= 7000 && $ds_capped >= 24000 && $personal_bba_count >= 4 && $leg_r7 >= 2) return 9;
        elseif ($ps_capped >= 700 && $fs >= 7000 && $ds_capped >= 12000 && $personal_bba_count >= 4 && $leg_r7 >= 1 && ($leg_r4 - 1) >= 1) return 8;

        elseif ($ps_capped >= 700 && $fs >= 7000 && $personal_bba_count >= 4 && $leg_r4 >= 2) return 7;
        elseif ($ps_capped >= 600 && $fs >= 4000 && $personal_bba_count >= 4 && $leg_r3 >= 2) return 6;
        elseif ($ps_capped >= 500 && $fs >= 2500 && $personal_bba_count >= 3) return 5;
        elseif ($ps_capped >= 400 && $personal_bba_count >= 2) return 4;
        elseif ($ps_capped >= 350 && $personal_bba_count >= 1) return 3;
        elseif ($ps_capped >= static::MINIMUM_PSC_REQUIREMENT) return 2;
        else return 1;

    }

    private function getMaxRankPerLegCount($member_id)
    {
        $sql = "
            SELECT
                rank_id,
                COUNT(1) AS `count`
            FROM (
                WITH RECURSIVE cte (user_id, sponsor_id, `level`, leg) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS sponsor_id,
                        1 AS `level`,
                        id AS leg
                    FROM users
                    WHERE sponsorid = :member_id
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS sponsor_id,
                        cte.`level` + 1 `level`,
                        cte.leg
                    FROM users p
                    INNER JOIN cte ON p.sponsorid = cte.user_id
                )
                SELECT MAX(IF(dr.min_rank_id > dr.rank_id, dr.min_rank_id, dr.rank_id)) rank_id, leg 
                FROM cte 
                JOIN cm_daily_ranks dr ON dr.user_id = cte.user_id
                WHERE dr.rank_date = @end_date 
                GROUP BY leg
            ) max_rank_per_leg
            GROUP BY rank_id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':member_id', $member_id);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $collection = collect($result)->mapWithKeys(function ($item) {
            return [(int)$item['rank_id'] => $item['count']];
        });

        return $collection;
    }

    private function getUsersWithVolumes()
    {
        $sql = "
            SELECT
                dv.user_id,
                dv.ps,
                a.users_ds,
                a.`users`,
                dv.ps_capped,
                dv.fs,
                dv.personal_bba_count,
                
                dv.`level`
            FROM cm_daily_volumes dv
            LEFT JOIN (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`, active, compress_level) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        id AS root_id,
                        0 AS `level`,
                        u.active,
                        0 compress_level
                    FROM users u
                    WHERE levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`,
                        u.active,
                        downline.compress_level + IF(u.active = 'Yes', 1, 0)
                    FROM users u
                    INNER JOIN downline ON u.sponsorid = downline.user_id
                    WHERE u.levelid = 3 AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, @affiliates))
                        -- AND downline.compress_level < 1
                        AND downline.`level` < 1
                )
                SELECT 
                    d.root_id AS user_id,
                    GROUP_CONCAT(d.user_id) `users`,
                    GROUP_CONCAT(dv.ds) `users_ds`
                FROM downline d
                JOIN cm_daily_volumes dv ON dv.user_id = d.user_id AND dv.volume_date = @end_date AND dv.ds > 0
                WHERE d.user_id <> d.root_id
                    -- AND d.active = 'Yes'
                GROUP BY d.root_id
            ) a ON a.user_id = dv.user_id
            WHERE dv.volume_date = @end_date
                -- AND (dv.ps > 0 OR a.users_ds IS NOT NULL)
            ORDER BY dv.`level` DESC, dv.user_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function setMainParameters()
    {
        $this->db->prepare("SET GLOBAL table_definition_cache = 524288")->execute();
        $this->db->prepare("
            SET @root_user_id = :root_user_id,
                @start_date = :start_date,
                @end_date = :end_date,
                @affiliates = :affiliates,
                @customers = :customers,
                @enrollment_kits = :enrollment_kits,
                @minimum_psc_requirement = :minimum_psc_requirement
            ")
            ->execute([
                ':root_user_id' => $this->root_user_id,
                ':start_date' => $this->start_date,
                ':end_date' => $this->end_date,
                ':customers' => $this->customers,
                ':affiliates' => $this->affiliates,
                ':enrollment_kits' => $this->enrollment_kits,
                ':minimum_psc_requirement' => static::MINIMUM_PSC_REQUIREMENT
            ]);
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
                ps,
                ps_capped,
                ds,
                ds_capped,
                fs,
                personal_bba_count,
                personal_bba_users,
                ds_capped_users,
                next_ds_capped,
                qualified_leg_r1_count,
                qualified_leg_r2_count,
                qualified_leg_r3_count,
                qualified_leg_r4_count,
                qualified_leg_r5_count,
                qualified_leg_r6_count,
                qualified_leg_r7_count,
                qualified_leg_r8_count,
                qualified_leg_r9_count,
                qualified_leg_r10_count,
                qualified_leg_r11_count,
                qualified_leg_r12_count,
                qualified_leg_r13_count,
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
                0 ps,
                0 ps_capped,
                0 ds,
                0 ds_capped,
                0 fs,
                0 personal_bba_count,
                NULL personal_bba_users,
                NULL ds_capped_users,
                0 next_ds_capped,
                0 qualified_leg_r1_count,
                0 qualified_leg_r2_count,
                0 qualified_leg_r3_count,
                0 qualified_leg_r4_count,
                0 qualified_leg_r5_count,
                0 qualified_leg_r6_count,
                0 qualified_leg_r7_count,
                0 qualified_leg_r8_count,
                0 qualified_leg_r9_count,
                0 qualified_leg_r10_count,
                0 qualified_leg_r11_count,
                0 qualified_leg_r12_count,
                0 qualified_leg_r13_count,
                d.level
            FROM downline d
            WHERE EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = d.user_id AND FIND_IN_SET(c.catid, @affiliates))            
            ON DUPLICATE KEY UPDATE
                ppv = 0,
                ppv_capped = 0,
                pcv = 0,
                pcv_capped = 0,
                ps = 0,
                ps_capped = 0,
                ds = 0,
                ds_capped = 0,
                fs = 0,
                personal_bba_count = 0,
                personal_bba_users = NULL,
                ds_capped_users = NULL,
                next_ds_capped = 0,
                qualified_leg_r1_count = 0,
                qualified_leg_r2_count = 0,
                qualified_leg_r3_count = 0,
                qualified_leg_r4_count = 0,
                qualified_leg_r5_count = 0,
                qualified_leg_r6_count = 0,
                qualified_leg_r7_count = 0,
                qualified_leg_r8_count = 0,
                qualified_leg_r9_count = 0,
                qualified_leg_r10_count = 0,
                qualified_leg_r11_count = 0,
                qualified_leg_r12_count = 0,
                qualified_leg_r13_count = 0,
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
                is_active
            )
            SELECT 
                user_id, 
                id AS volume_id, 
                volume_date AS rank_date, 
                1 AS rank_id, 
                1 AS min_rank_id, 
                1 AS paid_as_rank, 
                0 AS is_active
            FROM cm_daily_volumes
            WHERE volume_date = @end_date
            ON DUPLICATE KEY UPDATE 
                min_rank_id = 1,
                rank_id = 1,
                paid_as_rank_id = 1,
                volume_id = VALUES(volume_id),
                updated_at = CURRENT_TIMESTAMP();
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function log($message, $time = true)
    {
        if (php_sapi_name() !== 'cli') return;

        if ($time) {
            $t = Carbon::now()->toDateTimeString();
            $message = "[{$t}] - {$message}";
        }

        echo $message . PHP_EOL;
    }

    private function deleteAchievedRank()
    {
        $sql = "
            DELETE ar FROM cm_achieved_ranks ar
            WHERE ar.`date_achieved` = @end_date
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->rowCount();

        $this->log("{$rows} row(s) deleted");

    }

    private function deleteCareerRank()
    {
        $sql = "
            DELETE ar FROM cm_career_ranks ar
            WHERE ar.`date_achieved` = @end_date
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
                AND a.rank_id > dr.rank_id
                AND dr.rank_date > a.date_achieved
            ;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->rowCount();

        $this->log("{$rows} row(s) deleted");
    }

    private function deletePreviousHighestCareerRanksThisMonth()
    {
        $sql = "
            DELETE a FROM cm_career_ranks AS a
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

}