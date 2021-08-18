<?php


namespace Commissions;

use App\Rank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\DailyVolume;
use PDO;
use DateTime;


final class VolumesAndRanks extends Console
{
    const MIN_PRS = 500;

    protected $db;
    protected $end_date;
    protected $start_date;
    protected $affiliates;
    protected $customers;
    protected $root_user_id;
    protected $rank_requirements;
    protected $leader_rank;
    protected $team_leader;
    protected $sr_team_leader;
    protected $manager;

    public function __construct($end_date = null)
    {
        $this->db = DB::connection()->getPdo();
        $this->affiliates = config('commission.member-types.affiliates');
        $this->customers = config('commission.member-types.customers');
        $this->leader_rank = config('commission.ranks.leader');
        $this->team_leader = config('commission.ranks.team-leader');
        $this->sr_team_leader = config('commission.ranks.sr-team-leader');
        $this->manager = config('commission.ranks.manager');
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

            $this->log("Initializing Transaction Info");
            $this->initializeTransactionInfo();

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

            $this->log('Setting Personal Retail Sales (PRS)');
            $this->setPersonalRetailSales();

            $this->log('Setting Group Retail Sales Volume (GRS)');
            $this->setGroupRetailSales();

            $this->log('Setting Sponsored Qualified Representatives Users');
            $this->setQualifiedRepresentativesUsers();

            $this->log('Setting Sponsored Leader Or Higher Count');
            $this->setLeaderOrHigherUsers();

            $this->log('Setting Team Leader Or Higher Users');
            $this->setTeamLeaderUsers();

            $this->log('Setting Sr Team Leader Or Higher Count');
            $this->setSrTeamLeaderUsers();

            $this->log('Setting Manager Or Higher Users');
            $this->setManagerUsers();

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
                @min_prs = :min_prs,
                @leader_rank = :leader_rank,
                @team_leader = :team_leader,
                @sr_team_leader = :sr_team_leader,
                @manager = :manager
            ")
            ->execute([
                ':root_user_id' => $this->root_user_id,
                ':start_date' => $this->getStartDate(),
                ':end_date' => $this->getEndDate(),
                ':customers' => $this->customers,
                ':affiliates' => $this->affiliates,
                ':min_prs' => static::MIN_PRS,
                ':leader_rank' => $this->leader_rank,
                ':team_leader' => $this->team_leader,
                ':sr_team_leader' => $this->sr_team_leader,
                ':manager' => $this->manager,
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

    private function setPersonalRetailSales()
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
                    AND FIND_IN_SET(t.purchaser_catid, @affiliates)
                GROUP BY t.user_id
            ) AS a ON a.user_id = dv.user_id 
            LEFT JOIN (
                SELECT
                    ti.upline_id AS user_id,
                    SUM(COALESCE(t.computed_cv, 0)) AS cs
                FROM v_cm_transactions t
                JOIN cm_transaction_info ti ON ti.transaction_id = t.transaction_id
                WHERE t.transaction_date BETWEEN @start_date AND @end_date
                    AND t.`type` = 'product' 
                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                GROUP BY ti.upline_id
            ) AS c ON c.user_id = dv.user_id
            SET
                dv.prs = COALESCE(a.ps, 0) + COALESCE(c.cs, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setGroupRetailSales()
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
                        dv.prs AS volume
                    FROM users u
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = @end_date
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`,
                        dv.prs AS volume
                    FROM users u
                    JOIN downline ON u.sponsorid = downline.user_id
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = @end_date
                )
                SELECT 
                    d.root_id AS user_id,
                    SUM(d.volume) AS group_retail_volumes
                FROM downline d
                GROUP BY d.root_id
            ) a ON a.user_id = dv.user_id
            SET
                dv.grs = a.group_retail_volumes
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function setQualifiedRepresentativesUsers()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdv.user_id,
                    COUNT(dv.user_id) `count`,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', dv.user_id, 'prs', dv.prs)), 
                    ']') `users`
                FROM cm_daily_volumes sdv
                JOIN users u ON u.sponsorid = sdv.user_id
                JOIN cm_daily_volumes dv ON dv.user_id = u.id AND dv.volume_date = @end_date AND dv.prs >= @min_prs
                WHERE sdv.volume_date = @end_date
                GROUP BY sdv.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.sponsored_qualified_representatives_count = COALESCE(a.count, 0),
                dv.sponsored_qualified_representatives_users = a.users
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setLeaderOrHigherUsers()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdr.user_id,
                    COUNT(dr.user_id) `count`,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', dr.user_id, 'rank_id', dr.rank_id)), 
                    ']') `users`
                FROM cm_daily_ranks sdr
                JOIN users u ON u.sponsorid = sdr.user_id
                JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = @end_date AND dr.rank_id >= @leader_rank -- minimum rank for leader
                WHERE sdr.rank_date = @end_date
                GROUP BY sdr.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.sponsored_leader_or_higher_count = COALESCE(a.count, 0),
                dv.sponsored_leader_or_higher_users = a.users
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setTeamLeaderUsers()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdr.user_id,
                    COUNT(dr.user_id) `count`,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', dr.user_id, 'rank_id', dr.rank_id)), 
                    ']') `users`
                FROM cm_daily_ranks sdr
                JOIN users u ON u.sponsorid = sdr.user_id
                JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = @end_date AND dr.rank_id >= @team_leader -- minimum rank for team leader
                WHERE sdr.rank_date = @end_date
                GROUP BY sdr.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.team_leader_count = COALESCE(a.count, 0),
                dv.team_leader_users = a.users
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setSrTeamLeaderUsers()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdr.user_id,
                    COUNT(dr.user_id) `count`,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', dr.user_id, 'rank_id', dr.rank_id)), 
                    ']') `users`
                FROM cm_daily_ranks sdr
                JOIN users u ON u.sponsorid = sdr.user_id
                JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = @end_date AND dr.rank_id >= @sr_team_leader -- minimum rank for sr team leader
                WHERE sdr.rank_date = @end_date
                GROUP BY sdr.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.sr_team_leader_count = COALESCE(a.count, 0),
                dv.sr_team_leader_users = a.users
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();

        return $smt->fetchColumn();
    }

    private function setManagerUsers()
    {
        $sql = "
            UPDATE cm_daily_volumes dv
            LEFT JOIN (
                SELECT
                    sdr.user_id,
                    COUNT(dr.user_id) `count`,
                    CONCAT('[', 
                        GROUP_CONCAT(JSON_OBJECT('user_id', dr.user_id, 'rank_id', dr.rank_id)), 
                    ']') `users`
                FROM cm_daily_ranks sdr
                JOIN users u ON u.sponsorid = sdr.user_id
                JOIN cm_daily_ranks dr ON dr.user_id = u.id AND dr.rank_date = @end_date AND dr.rank_id >= @manager -- minimum rank for manager
                WHERE sdr.rank_date = @end_date
                GROUP BY sdr.user_id
            ) AS a ON a.user_id = dv.user_id 
            SET
                dv.manager_count = COALESCE(a.count, 0),
                dv.manager_users = a.users
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
                dr.is_active =  dv.prs >= @min_prs,
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

            $rank = $volume->rank;

            $rank->rank_id = $this->getRank($volume);
            $rank->paid_as_rank_id = $rank->rank_id > $rank->min_rank_id ? $rank->rank_id : $rank->min_rank_id;

            $rank->save();

            $this->saveAchievedRank($user_id, $rank->paid_as_rank_id);
        }
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
            if (
                +$volume->prs >= +$rank->prs_requirement
                && +$volume->grs >= +$rank->grs_requirement
                && +$volume->sponsored_qualified_representatives_count >= +$rank->sponsored_qualified_representatives
                && +$volume->sponsored_leader_or_higher_count >= +$rank->sponsored_leader_or_higher
                && +$volume->team_leader_count >= +$rank->team_leader_requirement
                && +$volume->sr_team_leader_count >= +$rank->sr_team_leader_requirement
                && +$volume->manager_count >= +$rank->manager_requirement
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

    private function initializeVolumes()
    {
        $sql = "
            INSERT INTO cm_daily_volumes (
                user_id, 
                volume_date,
                `level`,
                prs,
                grs,
                sponsored_qualified_representatives_count,
                sponsored_qualified_representatives_users,
                sponsored_leader_or_higher_count,
                sponsored_leader_or_higher_users,
                team_leader_count,
                team_leader_users,
                sr_team_leader_count,
                sr_team_leader_users,
                manager_count,
                manager_users
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
                d.level,
                0 prs,
                0 grs,
                0 sponsored_qualified_representatives_count,
                NULL sponsored_qualified_representatives_users,
                0 sponsored_leader_or_higher_count,
                NULL sponsored_leader_or_higher_users,
                0 team_leader_count,
                NULL team_leader_users,
                0 sr_team_leader_count,
                NULL sr_team_leader_users,
                0 manager_count,
                NULL manager_users
            FROM downline d
            WHERE EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = d.user_id AND FIND_IN_SET(c.catid, @affiliates))            
            ON DUPLICATE KEY UPDATE
                prs = 0,
                grs = 0,
                sponsored_qualified_representatives_count = 0,
                sponsored_qualified_representatives_users = NULL,
                sponsored_leader_or_higher_count = 0,
                sponsored_leader_or_higher_users = NULL,
                team_leader_count = 0,
                team_leader_users = NULL,
                sr_team_leader_count = 0,
                sr_team_leader_users = NULL,
                manager_count = 0,
                manager_users = NULL,
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
        echo "<pre>";
        echo "test";
        print_r($volume);
        print_r($next_rank);
        exit;
        if ($next_rank === null || $volume === null) return [];

        $needs = [];
        $prs_needs = $next_rank->prs_requirement - $volume->prs;
        $grs_needs = $next_rank->grs_requirement - $volume->grs;
        $sqr_needs = $next_rank->sponsored_qualified_representatives - $volume->sponsored_qualified_representatives_count;
        $slh_needs = $next_rank->sponsored_leader_or_higher - $volume->sponsored_leader_or_higher_count;

        $manager = config('commissions.ranks.manager');
        $tl_needs   = 0;
        $stl_needs  = 0;
        $m_needs    = 0;
        if($next_rank->id >= $manager)
        {
            $tl_needs = $next_rank->team_leader_requirement - $volume->team_leader_count;
            $stl_needs = $next_rank->sr_team_leader_requirement - $volume->sr_team_leader_count;
            $m_needs = $next_rank->manager_requirement - $volume->manager_count;
        }

        if ($prs_needs > 0) {
            $needs[] = [
                'value' => $prs_needs,
                'description' => 'PRS',
            ];
        }
        if ($grs_needs > 0) {
            $needs[] = [
                'value' => $grs_needs,
                'description' => 'GRS',
            ];
        }

        if ($sqr_needs > 0) {
            $needs[] = [
                'value' => $sqr_needs,
                'description' => 'Personal Sponsored Representative',
            ];
        }


        if ($slh_needs > 0) {
            $needs[] = [
                'value' => $slh_needs,
                'description' => 'Personal Sponsored Representative (Leader or higher)',
            ];
        }


        if ($tl_needs > 0) {
            $needs[] = [
                'value' => $tl_needs,
                'description' => 'Team Leaders',
            ];
        }


        if ($stl_needs > 0) {
            $needs[] = [
                'value' => $stl_needs,
                'description' => 'Senior Team Leaders',
            ];
        }


        if ($m_needs > 0) {
            $needs[] = [
                'value' => $m_needs,
                'description' => 'Managers',
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