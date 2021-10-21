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
        return count($this->getQualifiedAmbassador());
    }

    public function generateCommission($start, $length)
    {
        $this->log("Processing");
        $qualifiedAmbassadors = $this->getQualifiedAmbassador();
        foreach( $qualifiedAmbassadors as $u ){
            
            $this->log("Processing leadership pool for sponsor ID " . $u['sponsor_id']);

            $order_id   = $u['transaction_id'];
            $sponsor_id = $u['sponsor_id'];   
            $paid_as_rank = $u['paid_as_rank'];
            $bg5_count    = $u['bg5_count'];   
            $share        = $this->getBGShares($paid_as_rank);
            $total_shares = $this->getBGTotalShares($share, $bg5_count);
            $percentage = 2;
            $this->insertPayout(
                $sponsor_id,
                $sponsor_id,
                $total_shares,
                $percentage,
                $total_shares,
                "Leadership Pool | Member: $id has a total of $total_shares share",
                $order_id,
                0,
                $sponsor_id
            );
        }
    }

    public function getQualifiedAmbassador()
    {
        $start_date = $this->getPeriodStartDate();
        $end_date   = $this->getPeriodEndDate();        
        $last_30d_start = date('Y-m-d', strtotime('-30 days'));
        $last_30d_end   = date('Y-m-d');
        $affiliates     = config('commission.member-types.affiliates');

        $sql = "
            SELECT 
                t.transaction_id,
                t.sponsor_id,
                t.user_id,
                cdr.paid_as_rank,
                cdv.bg5_count
            FROM v_cm_transactions t  
            JOIN users u ON t.sponsor_id = u.id
            JOIN cm_daily_volumes cdv ON t.sponsor_id = cdv.user_id
            JOIN cm_daily_ranks cdr ON cdr.volume_id = cdv.id 
            WHERE 
                AND FIND_IN_SET(t.purchaser_catid, '$affiliates')
                AND 
                    t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND 
                (
                    SELECT SUM(dva.pv) 
                    FROM cm_daily_volumes dva 
                    WHERE dva.user_id = t.sponsor_id
                        AND dva.volume_date BETWEEN '$last_30d_start' AND '$last_30d_end'
                ) >= 50
                AND 
                AND u.active = 'Yes'
                AND cdr.rank_id IN(8,9,10)
                AND cdr.is_system_active = 1
            GROUP BY t.sponsor_id
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getBGTotalShares($share, $bg5_count)
    {  
        $total_shares = $share + $bg5_count;

        return $total_shares;
    }    

    private function getBGShares($paid_as_rank)
    {  
        $shares = [
              8 => 1,
              9 => 2,
              10 => 3
        ];

        return $shares[$paid_as_rank];
    }
}