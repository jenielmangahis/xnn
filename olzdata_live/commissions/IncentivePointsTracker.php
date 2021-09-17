<?php


namespace Commissions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use \PDO;
use DateTime;


final class IncentivePointsTracker extends Console
{
    protected $db;
    protected $end_date;
    protected $start_date;
    protected $activeIncentives;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->activeIncentives = [];
    }

    private function process()
    {
        DB::transaction(function () {

            $this->log("Deleting incentive points records");
            $this->deleteIncentivePointsRecords();

            $this->log("Initialize incentive points records");
            $this->initializeIncentivePointsRecords();

            $this->log("Set incentive prs records");
            $this->setIncentivePrs();

            $this->log("Set Point for every PRS");
            $this->setPointPerPrs();

            $this->log("Set incentive promote to or higher rank");
			$this->setPromoteToOrHigher();

            $this->log("Set incentive that has new enrolled representative");
			$this->setMembersWithNewRepresentatives();

			$this->log("Set Double points for new representative");
			$this->setDoublePointsForNewRepresentative();

            $this->log("Set incentive double points");
			$this->setDoublePoints();

            $this->log("Set Bonus Points");
            $this->setRepresentativeBonusPoints();

            $this->log("Set Total Points");
            $this->setTotalPoints();

            $this->log("Close Previous Incentives");
            $this->lockedPreviousIncentives(); 

        }, 3);
    }

    private function deleteIncentivePointsRecords()
    {
        $sql = "
            DELETE cdip FROM cm_daily_incentive_points cdip
            JOIN cm_incentive_tool_settings cits ON cits.id = cdip.settings_id
            WHERE incentive_point_date = @end_date AND cits.is_locked = 0 AND cits.is_active = 1;
        ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
    }

    private function initializeIncentivePointsRecords()
    {
        if(count($this->activeIncentives) > 0) {
            foreach($this->activeIncentives as $incentive) {
                $this->createIncentivePointsRecords($incentive['id']);
            }
       }
    }

    private function createIncentivePointsRecords($settings_id)
    {
        $sql = "            
            INSERT cm_daily_incentive_points(
                user_id,
                settings_id,
                volume_id,
                incentive_point_date,
                prs,
                points,
                bonus_points
            )
            SELECT 
                dv.user_id,
                :settings_id as settings_id,
                dv.id AS volume_id,
                dv.volume_date AS incentive_point_date,
                0 AS prs,
                0 AS points,
                0 AS bonus_points
            FROM cm_daily_volumes dv
            WHERE dv.volume_date = @end_date
            ON DUPLICATE KEY UPDATE
                prs = 0,
                points = 0,
                bonus_points = 0,
                updated_at = CURRENT_TIMESTAMP();  
        ";

        $stmt = $this->db->prepare($sql);
		$stmt->bindParam(':settings_id', $settings_id);
        $stmt->execute();
    }

    private function setIncentivePrs()
    {
        $sql = "
            UPDATE cm_daily_incentive_points cdip
            LEFT JOIN cm_daily_volumes dv ON dv.`id` = cdip.`volume_id`
            SET cdip.`prs` = dv.`prs`
            WHERE dv.`volume_date` = @end_date;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    private function setPointPerPrs()
    {
        if(count($this->activeIncentives) > 0) {
            $sql = "
                UPDATE cm_daily_incentive_points cdip
                JOIN cm_incentive_tool_settings cits ON cits.id = cdip.`settings_id`
                SET cdip.points = (cdip.prs * cits.`points_per_prs`)
                WHERE cdip.`incentive_point_date` = @end_date
                AND cits.`is_active` = 1 
                AND cits.`is_locked` = 0 
                AND cits.`is_points_per_prs` = 1
                AND cits.end_date >= @end_date;
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
    }

    private function setDoublePoints()
    {
        if(count($this->activeIncentives) > 0) {
            foreach($this->activeIncentives as $incentive) {
                if($incentive['is_double_points_on']) {

                    $this->log("Incentive: ". $incentive['title']." - points will be doubled");
                    $sql = "
                        UPDATE cm_daily_incentive_points cdip
                        SET cdip.`points` = cdip.`points` * 2
                        WHERE cdip.`incentive_point_date` BETWEEN :start_date AND :end_date
                        AND cdip.`incentive_point_date` = @end_date
                        AND cdip.`settings_id` = :settings_id;
                    ";

                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':settings_id', $incentive['id']);
                    $stmt->bindParam(':start_date', $incentive['double_points_start_date']);
                    $stmt->bindParam(':end_date', $incentive['double_points_end_date']);
                    $stmt->execute();
                }
            }
        }
    }

    private function setPromoteToOrHigher()
    {
        if(count($this->activeIncentives) > 0) {
            foreach($this->activeIncentives as $incentive) {
                if($incentive['is_promote_to_or_higher']) {

                    $sql = "                        
                        UPDATE cm_daily_incentive_points cdip
                        JOIN cm_achieved_ranks car ON car.user_id = cdip.user_id
                        JOIN cm_incentive_tool_settings cits ON cits.id = cdip.settings_id
                        SET cdip.points = cdip.points + cits.promote_to_or_higher_points
                        WHERE car.`rank_id` >= :rank_id
                        AND car.date_achieved BETWEEN :start_date AND :end_date
                        AND cits.id = :settings_id
                        AND cdip.incentive_point_date = CURRENT_DATE()
                    ";

                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':rank_id', $incentive['rank_id']);
                    $stmt->bindParam(':start_date', $incentive['start_date']);
                    $stmt->bindParam(':end_date', $incentive['end_date']);
                    $stmt->bindParam(':settings_id', $incentive['id']);
                    $stmt->execute();
                }
            }
        }
    }

    private function setMembersWithNewRepresentatives()
    {
        if(count($this->activeIncentives) > 0) {
            foreach($this->activeIncentives as $incentive) {

                if($incentive['is_has_new_representative']) {

                    // get users between this contest
                    $representatives = $this->getQualifiedRepresentatives($incentive['start_date'], $incentive['end_date']);

                    // get new representative 
                    foreach($representatives as $representative) {

                        $new_representatives = $this->getQualifiedNewRepresentatives($incentive['new_representative_start_date'], $incentive['new_representative_end_date'], $representative['user_id']);

                        if(count($new_representatives) > 0) {
                            foreach($new_representatives as $new_rep) {

                                // check the prs and first n days of new rep
                                $flag = $this->checkPrsOfNewRepresentative($incentive['new_representative_min_prs'], $incentive['new_representative_first_n_days'], $new_rep['user_id']);
    
                                if($flag) {

                                    $this->log(" Sponsor:".$representative['user_id']. " - has new Representative:".$new_rep['user_id']);

                                    // update prs of rep
                                    $sql = "
                                        UPDATE cm_daily_incentive_points cdip
                                        SET cdip.points = cdip.points + :new_rep_points
                                        WHERE cdip.incentive_point_date = @end_date
                                        AND cdip.`user_id` = :user_id
                                        AND cdip.id = :cdip_id
                                    ";
    
                                $stmt = $this->db->prepare($sql);
                                $stmt->bindParam(':new_rep_points', $incentive['new_representative_points']);
                                $stmt->bindParam(':user_id', $representative['user_id']);
                                $stmt->bindParam(':cdip_id', $representative['id']);
                                $stmt->execute();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function getQualifiedRepresentatives($start_date, $end_date)
    {
        $sql = "
            SELECT dv.user_id, cdip.id FROM cm_daily_volumes dv 
            JOIN cm_daily_incentive_points cdip ON dv.id = cdip.volume_id
            WHERE dv.volume_date BETWEEN :start_date AND :end_date
            GROUP BY dv.user_id ASC;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $representatives = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $representatives;
    }

    private function getQualifiedNewRepresentatives($ner_rep_start_date, $new_rep_end_date, $sponsor_id)
    {
        $sql = "
            SELECT ca.user_id FROM users u
            JOIN cm_affiliates ca ON u.id = ca.user_id
            WHERE ca.affiliated_date BETWEEN :new_rep_start_date AND :new_rep_end_date
            AND FIND_IN_SET(ca.cat_id, '13')
            AND u.`active` = 'Yes'
            AND u.sponsorid = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':new_rep_start_date', $ner_rep_start_date);
        $stmt->bindParam(':new_rep_end_date', $new_rep_end_date);
        $stmt->bindParam(':user_id', $sponsor_id);
        $stmt->execute();

        $new_representatives = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $new_representatives;
    }

    private function checkPrsOfNewRepresentative($new_rep_min_prs, $new_rep_first_n_days, $new_rep_user_id) 
    {
        $sql = "
            SELECT dv.user_id, dv.volume_date, ca.affiliated_date, dv.prs FROM cm_daily_volumes dv 
            JOIN cm_affiliates ca ON dv.`user_id` = ca.`user_id`
            WHERE ca.`affiliated_date` BETWEEN ca.`affiliated_date` AND DATE_ADD(ca.`affiliated_date`, INTERVAL :n_days DAY)
            AND dv.prs >= :new_representative_min_prs
            AND dv.`user_id` = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':new_representative_min_prs', $new_rep_min_prs);
        $stmt->bindParam(':n_days', $new_rep_first_n_days);
        $stmt->bindParam(':user_id', $new_rep_user_id);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flag = false;
        if(count($data) > 0) {
            $flag = true;
        }
        return $flag;
    }
    

    private function setDoublePointsForNewRepresentative()
    {
        if(count($this->activeIncentives) > 0) {
            foreach($this->activeIncentives as $incentive) {
                if($incentive['is_double_points_new_representative']) {
                    // get users between this contest
                    $representatives = $this->getQualifiedRepresentatives($incentive['start_date'], $incentive['end_date']);

                    // get new representative 
                    foreach($representatives as $representative) {

                        $new_representatives = $this->getQualifiedNewRepresentatives($incentive['double_points_new_representative_start_date'], $incentive['double_points_new_representative_end_date'], $representative['user_id']);

                        if(count($new_representatives) > 0) {
                            foreach($new_representatives as $new_rep) {

                                $this->log("Double the points of New Representative:".$new_rep['user_id']);

                                $sql = "
                                    UPDATE cm_daily_incentive_points cdip
                                    JOIN cm_affiliates ca ON cdip.`user_id` = ca.`user_id`
                                    SET cdip.points = cdip.`points` * 2
                                    WHERE cdip.`incentive_point_date` = @end_date
                                    AND ca.affiliated_date BETWEEN ca.`affiliated_date` AND DATE_ADD(ca.`affiliated_date`, INTERVAL :n_days DAY)
                                    AND cdip.`user_id` = :user_id
                                    AND cdip.settings_id = :settings_id
                                ";

                                $stmt = $this->db->prepare($sql);
                                $stmt->bindParam(':user_id', $new_rep['user_id']);
                                $stmt->bindParam(':n_days', $incentive['double_points_new_representative_first_n_days']);
                                $stmt->bindParam(':settings_id', $incentive['id']);
                                $stmt->execute();

                            }
                        }
                    }
                }
            }
        }
    }

    private function setRepresentativeBonusPoints()
    {
        if(count($this->activeIncentives) > 0) {
            foreach($this->activeIncentives as $incentive) {
                $users_bonuses = $this->getUsersBonus($incentive['id']);

                if(count($users_bonuses) > 0) {
                    $totals = [];

                    foreach($users_bonuses as $bonus) {

                        if(array_key_exists($bonus['user_id'], $totals)) {
                            $totals[$bonus['user_id']]['arbitrary_ids'][] = $bonus['id'];
                            $totals[$bonus['user_id']]['total'] += $bonus['bonus_points'];
                        } else {
                            $totals[$bonus['user_id']]['arbitrary_ids'][] = $bonus['id'];
                            $totals[$bonus['user_id']]['total'] = $bonus['bonus_points'];
                        }
                    }

                    foreach($totals as $key => $total) {

                        $sql = "
                            UPDATE cm_daily_incentive_points cdip
                            SET cdip.bonus_points = :total, cdip.arbitrary_points_ids = :arbitrary_ids
                            WHERE cdip.incentive_point_date = @end_date
                            AND cdip.`user_id` = :user_id
                            AND cdip.settings_id = :settings_id
                        ";
                       
                        $ids = json_encode($total['arbitrary_ids']);
                        
                        $stmt = $this->db->prepare($sql);
                        $stmt->bindParam(':total', $total['total']);
                        $stmt->bindParam(':arbitrary_ids', $ids);
                        $stmt->bindParam(':user_id', $key);
                        $stmt->bindParam(':settings_id', $incentive['id']);
                        $stmt->execute();
                    }
                }
            }
        }
    }

    private function setTotalPoints()
    {
		$sql = "
			UPDATE cm_daily_incentive_points cdip
			SET cdip.total_points = cdip.`points` + cdip.bonus_points
			WHERE cdip.`incentive_point_date` = @end_date
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute();
    }

    private function lockedPreviousIncentives()
    {
        $sql = "
            UPDATE cm_incentive_tool_settings
            SET is_locked = 1, is_active = 0
            WHERE is_locked = 0 AND is_active = 1 AND end_date < @end_date
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    
    }

    private function getUsersBonus($settings_id)
    {
        $sql = "
            SELECT cap.* FROM cm_arbitrary_points cap
            JOIN cm_incentive_tool_settings cits ON cits.id = cap.`settings_id`
            WHERE cits.id = :id
            ORDER BY user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $settings_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    private function getIncentiveToolSettings()
    {
        $sql = "
            SELECT * FROM cm_incentive_tool_settings 
            WHERE is_locked = 0 AND is_active = 1 
            AND start_date <= @end_date
            AND end_date >= @end_date
            ORDER BY start_date ASC;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($result) > 0) {
            return $result;
        } else {
            $end_date = $this->end_date;
            die("No Active Incentive for date: $end_date \n");
        }
    }

    private function setMainParameters()
    {
        $this->db->prepare("
            SET 
                @end_date = :end_date
            ")
            ->execute([
                ':end_date' => $this->getEndDate()
            ]);
    }

    private function getRepresentativesToday()
    {
        $sql = "
            SELECT count(*) AS target FROM cm_daily_volumes
            WHERE volume_date = CURRENT_DATE();
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchColumn();

        return $result;
    }

    public function run()
    {
        $end_date = date('Y-m-d', strtotime("today"));
        $this->setDates($end_date);

        $this->setMainParameters();

        $this->activeIncentives = $this->getIncentiveToolSettings();

        if(count($this->activeIncentives) > 0) {

            // check if has target users for today
            $reps = $this->getRepresentativesToday();

            if($reps > 0) {
                $this->process();
            } else {
                $this->log("No records found");
            }
        }
    }

    protected function setDates($end_date = null)
    {
        $end_date = $this->getRealCarbonDateParameter($end_date);

        $this->end_date = $end_date->format("Y-m-d");
        $this->start_date = $end_date->copy()->firstOfMonth()->format("Y-m-d");
    }

    public function getEndDate()
    {
        if (!isset($this->end_date)) {
            throw new Exception("End date is not set.");
        }

        return $this->end_date;
    }
}