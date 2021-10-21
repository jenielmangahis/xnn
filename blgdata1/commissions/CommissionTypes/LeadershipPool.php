<?php
/**
 * Created by 
 * User: Jeniel Mangahis
 * Date: 10/21/2021
 * Time: 10:00 PM
 */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;
use \PDO;


class LeadershipPool extends CommissionType
{
  

    public function count()
    {
        return count($this->getQualifiedUsers());
    }

    public function generateCommission($start, $length)
    {
        $this->log("Processing");
        $qualifiedUsers = $this->getQualifiedUsers();
        foreach( $qualifiedUsers as $u ){

        }
    }

    public function getQualifiedUsers()
    {
        $start_date = $this->getPeriodStartDate();
        $end_date   = $this->getPeriodEndDate();        
        $last_30d_start = date('Y-m-d', strtotime('-30 days'));
        $last_30d_end   = date('Y-m-d');

        $sql = "
            SELECT 
                t.transaction_id,
                t.sponsor_id,
                t.user_id
            FROM v_cm_transactions t  
            JOIN users u ON t.sponsor_id = u.id
            JOIN cm_daily_volumes cdv ON t.sponsor_id = cdv.user_id
            JOIN cm_daily_ranks cdr ON cdr.volume_id = cdv.id 
            WHERE 
                t.purchaser_catid IN('13,16,14,8033')
                AND 
                    t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND 
                (
                    SELECT SUM(dva.pv) 
                    FROM cm_daily_volumes dva 
                    WHERE dva.user_id = t.sponsor_id
                        AND dva.volume_date BETWEEN '$last_30d_start' AND '$last_30d_end'
                ) >= 50
                AND u.active = 'Yes'
                AND cdr.rank_id = 1
                AND cdr.is_system_active = 1
            GROUP BY t.sponsor_id
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    
}