<?php


namespace Commissions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use \PDO;
use DateTime;


final class EnergyAccount extends Console
{
    protected $db;

    public function __construct($end_date = null)
    {
        $this->db = DB::connection()->getPdo();
    }

    private function process()
    {
        
    }

    public function updateEnergyCountStatus()
    {

	    $sql = "
	        SELECT * 
	        FROM `energy_account_status_sept13` 
	        ORDER BY `id` DESC
	    ";

	    $smt = $this->db->prepare($sql);
	    $smt->execute();
	    $enery_count_status = $smt->fetchAll(PDO::FETCH_ASSOC);

	    foreach($enery_count_status as $ecs){
	        $date_created_condition = date("Y-m-d");
	        $current_date = date("Y-m-d");

	        $sql = "
	            SELECT
	                ceasl.status_type
	            FROM cm_energy_account_status_logs ceasl
	            WHERE ceasl.plank_energy_account_id = :plank_energy_account_id
	            AND ceasl.created_at >= :date_created_condition
	            ORDER BY ceasl.created_at DESC LIMIT 1
	        ";
	        $smt = $this->db->prepare($sql);
	        $smt->bindParam(':plank_energy_account_id', $ecs['plankEnergyAccountId']);
	        $smt->bindParam(':date_created_condition', $date_created_condition);
	        $smt->execute();
	        $plank_energy_account = $smt->fetchAll(PDO::FETCH_ASSOC);

	        $sql = "
	            SELECT `id`, `plank_energy_account_id`
	            FROM cm_energy_accounts cea
	            WHERE cea.plank_energy_account_id = :plank_energy_account_id
	            ORDER BY cea.created_at DESC LIMIT 1
	        ";
	        $smt = $this->db->prepare($sql);
	        $smt->bindParam(':plank_energy_account_id', $ecs['plankEnergyAccountId']);
	        $smt->execute();
	        $energy_account = $smt->fetch(PDO::FETCH_ASSOC);

	        //STATUS LIKE "%2"
	        if( substr_compare($ecs['status'], 2, -strlen(2)) === 0 && is_null($plank_energy_account) ){
	            //Update cm_energy_accounts [status = 2]
	            $this->updateCmEnergyAccountByStatus(2, $ecs['plankEnergyAccountId']);
	            //Delete from cm_energy_account_logs where status >= 3
	            $sql = " 
	                DELETE FROM cm_energy_account_logs 
	                WHERE `status` >= 3 AND `energy_account_id`= :energy_account_id

	            ";
	            $smt = $this->db->prepare($sql);
	            $smt->bindParam(':energy_account_id', $energy_account['id']);
	            $smt->execute();
	        }

	        //STATUS LIKE "%4"
	        if( substr_compare($ecs['status'], 4, -strlen(4)) === 0 ){
	           //no change
	        }

	        //STATUS LIKE "%5"
	        if( substr_compare($ecs['status'], 5, -strlen(5)) === 0 ){
	            if( $ecs['date_starts_flowing '] <= $current_date && is_null($plank_energy_account) ){
	                //Update cm_energy_accounts [status = 5]
	                $this->updateCmEnergyAccountByStatus(5, $ecs['plankEnergyAccountId']);
	                //Delete from cm_energy_account_logs where status >= 6
	                $sql = " 
	                    DELETE FROM cm_energy_account_logs 
	                    WHERE `status` >= 6 AND `energy_account_id`= :energy_account_id

	                ";
	                $smt = $this->db->prepare($sql);
	                $smt->bindParam(':energy_account_id', $energy_account['id']);
	                $smt->execute();
	            } 

	            if( $ecs['date_starts_flowing '] > $current_date ){
	                //Update cm_energy_accounts [status = 4]
	                $this->updateCmEnergyAccountByStatus(4, $ecs['plankEnergyAccountId']);
	                //Delete from cm_energy_account_logs where status >= 5
	                $sql = " 
	                    DELETE FROM cm_energy_account_logs 
	                    WHERE `status` >= 5 AND `energy_account_id`= :energy_account_id

	                ";
	                $smt = $this->db->prepare($sql);
	                $smt->bindParam(':energy_account_id', $energy_account['id']);
	                $smt->execute();
	            }
	        }

	        //STATUS LIKE "%7"
	        if( substr_compare($ecs['status'], 7, -strlen(7)) === 0 ){
	            if( $ecs['date_stops_flowing'] <= $current_date && is_null($plank_energy_account) ){
	                //Update cm_energy_accounts [status = 7]
	                $this->updateCmEnergyAccountByStatus(7, $ecs['plankEnergyAccountId']);
	                //Insert to cm_energy_account_logs [status = 7, created_at = :date_starts_flowing]
	                $sql = "
	                    INSERT INTO cm_energy_account_logs (status, created_at)
	                    VALUES(:status, :created_at)
	                ";

	                $stmt = $this->db->prepare($sql);
	                $stmt->bindParam(':status', 7);
	                $stmt->bindParam(':created_at', $ecs['date_starts_flowing']);
	                $stmt->execute();

	            }

	            if( is_null($ecs['date_stops_flowing']) && is_null($plank_energy_account) ){
	                //Update cm_energy_accounts [status = 7]
	                $this->updateCmEnergyAccountByStatus(7, $ecs['plankEnergyAccountId']);
	                //Insert to cm_energy_account_logs [status = 7, created_at = NOW()]
	                $sql = "
	                    INSERT INTO cm_energy_account_logs (status, created_at)
	                    VALUES(:status, :created_at)
	                ";

	                $stmt = $this->db->prepare($sql);
	                $stmt->bindParam(':status', 7);
	                $stmt->bindParam(':created_at', date("Y-m-d H:i:s"));
	                $stmt->execute();
	            }

	            if( $ecs['date_stops_flowing'] > $current_date ){
	                //Update cm_energy_accounts [status = 6]
	                $this->updateCmEnergyAccountByStatus(6);
	                //Delete from cm_energy_account_logs where status = 7
	                $sql = " 
	                    DELETE FROM cm_energy_account_logs 
	                    WHERE `status` = 7 AND `energy_account_id`= :energy_account_id

	                ";
	                $smt = $this->db->prepare($sql);
	                $smt->bindParam(':energy_account_id', $energy_account['id']);
	                $smt->execute();
	            }
	        }
	    }
	}

	public function updateCmEnergyAccountByStatus( $status, $plank_energy_account_id )
	{
	    $sql = "
	        UPDATE cm_energy_accounts 
	        SET `status` = :status
	        WHERE `plank_energy_account_id` = :plank_energy_account_id 
	    ";
	    $smt = $this->db->prepare($sql);
	    $smt->bindParam(':status', $status);
	    $smt->bindParam(':plank_energy_account_id', $plank_energy_account_id);
	    $smt->execute();
	}
}