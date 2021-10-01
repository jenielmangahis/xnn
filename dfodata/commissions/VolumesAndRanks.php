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
            $this->initializeVolumes();

            $this->log('Initializing Ranks');
            $this->initializeRanks();

            $this->log('Setting PV');
            $this->setPv();

            $this->log('Setting PV From customer order sponsored by customers');
            $this->setPvCustomerOrder();

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
            JOIN cm_daily_volumes dv ON dv.id = dr.volume_id AND dv.volume_date = @end_date
            JOIN users u ON u.id = dv.user_id
            SET 
                dr.is_active =  IF(dv.pv >= 100, 1, 0),
                dr.is_system_active = (u.active = 'Yes')
            WHERE dr.rank_date = @end_date
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setRanks()
    {
        $volumes = DailyVolume::date($this->getEndDate())->orderBy('user_id', 'desc')->get();

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

        $pv_needs = $next_rank->pv - $volume->pv;
        $l1v_needs = $next_rank->liv - $volume->l1v;

         if($next_rank->pv >= 100 && $next_rank->liv >= 4000){ 
            $consultant_1_requirement = 0; 
            $consultant_2_requirement = 0;
            $consultant_3_requirement = 1;  
        }
       
        if($next_rank->pv >= 100 && $next_rank->liv <= 3999){ 
            $consultant_1_requirement = 0; 
            $consultant_2_requirement = 1;
            $consultant_3_requirement = 0;
        }

        if($next_rank->pv >= 100 && $next_rank->liv <= 1199){ 
            $consultant_1_requirement = 1; 
            $consultant_2_requirement = 0;
            $consultant_3_requirement = 0;
        }       

        if($pv_needs > 0) {
            $needs[] = [
                'value' => $pv_needs,
                'description' => 'PV',
            ];
        }

        if($l1v_needs > 0) {
            $needs[] = [
                'value' => $l1v_needs,
                'description' => 'L1V',
            ];
        }

       
        if ($consultant_3_requirement > 0) {
            $needs[] = [
                'value' => $consultant_3_requirement,
                'description' => 'Consultant 3'
            ];
        }

        if ($consultant_2_requirement > 0) {
            $needs[] = [
                'value' => $consultant_2_requirement,
                'description' => 'Consultant 2'
            ];
        }

        if ($consultant_1_requirement > 0) {
            $needs[] = [
                'value' => $consultant_1_requirement,
                'description' => 'Consultant 1'
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
                l1v,
                level
            )
            WITH RECURSIVE downline (user_id, parent_id, `level` , `active`) AS (
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
                0 l1v,
                d.level 
            FROM downline d
            ON DUPLICATE KEY UPDATE
                pv = 0,
                l1v = 0,
                level = d.level,
                created_at = CURRENT_TIMESTAMP(),
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
                created_at = CURRENT_TIMESTAMP(),
                updated_at = CURRENT_TIMESTAMP();
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
                    SUM(COALESCE(t.computed_cv, 0)) As pv
                FROM v_cm_transactions t
                WHERE transaction_date BETWEEN @start_date AND @end_date
                    AND t.`type` = 'product'
                    AND FIND_IN_SET(t.purchaser_catid, @affiliates)
                    -- AND FIND_IN_SET(t.sponsor_catid, @affiliates)
                GROUP BY t.user_id
            ) AS a ON a.user_id = dv.user_id 
            LEFT JOIN (
                SELECT
                    t.sponsor_id AS user_id,
                    SUM(COALESCE(t.computed_cv, 0)) AS cs
                FROM v_cm_transactions t
                WHERE t.transaction_date BETWEEN @start_date AND @end_date
                    AND t.`type` = 'product' 
                    AND FIND_IN_SET(t.purchaser_catid, @customers)
                GROUP BY t.sponsor_id
            ) AS c ON c.user_id = dv.user_id
            SET
                dv.pv = COALESCE(a.pv, 0)
               
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
                SELECT 
                    SUM(dv.pv) AS total_pv,
                    dv.user_id
                FROM cm_daily_volumes dv
                JOIN users u ON dv.user_id = u.id 
                WHERE u.sponsorid = dv.user_id AND dv.volume_date = @end_date
            ) AS a ON a.user_id = dv.user_id             
            SET
                dv.l1v = COALESCE(a.total_pv, 0)
            WHERE dv.volume_date = @end_date
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    private function setPvCustomerOrder() {
        $sql = "
        SELECT
            t.sponsor_id AS user_id,
            SUM(COALESCE(t.computed_cv, 0)) AS cs
        FROM v_cm_transactions t
        WHERE t.transaction_date BETWEEN @start_date AND @end_date
            AND t.`type` = 'product' 
            AND FIND_IN_SET(t.purchaser_catid, @customers)
            AND FIND_IN_SET(t.sponsor_catid, @customers)"
        ;

        $stmt = $this->db->prepare($sql);        
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as $order) {
            $this->log("went order test " . $order->user_id);
            $repIDs = $this->nextUplineRep($order->user_id);
            foreach( $repIDs as $repID ){
                $this->log("went repid : " . $repID->user_id);
                $sql = "
                    UPDATE cm_daily_volumes dv
                        SET pv = pv + :pv
                    WHERE user_id = :repID AND volume_date = @end_date
                ";

                $smt = $this->db->prepare($sql);
                $smt->bindParam(':repID', $repID->user_id);
                $smt->bindParam(':pv', $order->cs);
                $smt->execute();
            }
        }
    }

    private function nextUplineRep($user_id) {
       /*$sql = "
            WITH RECURSIVE upline (user_id, parent_id, `level`) AS (
                SELECT
                    id AS user_id,
                    sponsorid AS parent_id,
                    1 AS `level`
                FROM users
                WHERE id = :user_id
                
                UNION ALL
                
                SELECT
                    u.id AS user_id,
                  u.sponsorid AS parent_id,
                  upline.`level` + 1 `level`
                FROM users u
                INNER JOIN upline ON upline.parent_id = u.id
            )
            SELECT 
                u.parent_id AS user_id
            FROM upline u 
            WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.parent_id)
            LIMIT 1;
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':user_id', $user_id);        
        $smt->execute();
        return $smt->fetchColumn();*/

        $sql = "
            WITH RECURSIVE upline (user_id, parent_id) AS (
                SELECT
                    id AS user_id,
                    sponsorid AS parent_id
                FROM users
                WHERE id = :v_user_id
                
                UNION ALL
                
                SELECT
                    u.id AS user_id,
                  u.sponsorid AS parent_id
                FROM users u
                INNER JOIN upline ON upline.parent_id = u.id
            )
            SELECT 
                u.parent_id AS user_id
            FROM upline u 
            WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.parent_id)
        ";

        $stmt = $this->db->prepare($sql);  
        $smt->bindParam(':v_user_id', $user_id);       
        $stmt->execute();
        $this->log("went execute");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}