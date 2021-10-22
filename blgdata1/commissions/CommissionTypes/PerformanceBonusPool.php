<?php
/**
 * Created by 
 * User: Jeniel Mangahis
 * Date: 10/22/2021
 * Time: 07:00 PM
 */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;
use \PDO;


class PerformanceBonusPool extends CommissionType
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
            
            $this->log("Processing performance bonus pool for sponsor ID " . $u['sponsor_id']);
            $order_id = 0;
            $sponsor_id = $u['sponsor_id'];   
            $paid_as_rank = $u['paid_as_rank_id'];
            $bg5_count    = $u['bg5_count'];   
            $bg8_count    = $u['bg8_count'];   
            $share        = $this->getBGShares($paid_as_rank);
            $total_shares = $this->getBGTotalShares($share, $bg5_count, $bg8_count);
            $percentage = 1;
            $this->insertPayout(
                $sponsor_id,
                $sponsor_id,
                $total_shares,
                $percentage,
                $total_shares,
                "Performance Bonus Pool | Member: $sponsor_id has a total of $total_shares share",
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
                u.id AS user_id,
                cdr.paid_as_rank_id,
                u.sponsorid AS sponsor_id,
                cdv.bg5_count,
                cdv.bg8_count
            FROM cm_daily_volumes cdv
            JOIN cm_daily_ranks cdr ON cdr.volume_id = cdv.id 
            JOIN users u ON u.id = cdv.user_id
            WHERE u.active = 'Yes' 
                AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = cdv.user_id AND FIND_IN_SET(a.cat_id,'$affiliates'))
                AND cdr.rank_id IN(11,12,13)
                AND cdr.is_system_active = 1
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getBGTotalShares($share, $bg5_count, $bg8_count )
    {  
        $total_shares = $share + $bg5_count + $bg8_count;

        return $total_shares;
    }    

    private function getBGShares($paid_as_rank)
    {  
        $shares = [
              11 => 4,
              12 => 5,
              13 => 6
        ];

        return $shares[$paid_as_rank];
    }
}