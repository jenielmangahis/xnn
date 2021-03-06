<?php
/**
 * Created by 
 * User: Jeniel Mangahis
 * Date: 10/22/2021
 * Time: 11:00 PM
 */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;
use \PDO;


class BigDogBonusPool extends CommissionType
{
  

    public function count()
    {
        return count($this->getQualifiedAmbassador());
    }

    public function generateCommission($start, $length)
    {
        $this->log("Processing");
        $qualifiedAmbassadors = $this->getQualifiedAmbassador();
        $totalGlobalShare     = $this->getCompanyGlobalSales();
        $percentage = 1;
        $total_shares = 0;
        $com_data     = array();
        foreach( $qualifiedAmbassadors as $u ){
            $paid_as_rank = $u['paid_as_rank_id'];
            $share        = $this->getBGShares($paid_as_rank);
            $bg5_count    = $u['bg5_count']; 
            $bg9_count    = $u['bg9_count'];     
            $bg10_count    = $u['bg10_count'];   
            $bg11_count    = $u['bg11_count'];   
            $bg12_count    = $u['bg12_count'];              
            $total_shares += $this->getBGTotalShares($share, $bg5_count, $bg9_count, $bg10_count, $bg11_count, $bg12_count);
            $com_data[]= [
                'sponsor_id' => $u['sponsor_id'],
                'share_amount' => $this->getBGTotalShares($share, $bg5_count, $bg9_count, $bg10_count, $bg11_count, $bg12_count),
            ];
        }

        $percentage_global_share = $totalGlobalShare['total_amount'] * ($percentage/100);
        $per_share_value = $total_shares / $percentage_global_share  ;
        $this->log("Global Company Sales:" . $totalGlobalShare['total_amount']);
        $this->log("1% of Global Company Sales:" . $percentage_global_share);
        $this->log("Per Share Value:" . $per_share_value);
        $this->log("Total Share:" . $total_shares);

        foreach( $com_data as $com ){
            
            $this->log("Processing Big dog bonus Pool for sponsor ID " . $com['sponsor_id']);

            $sponsor_id = $com['sponsor_id'];
            $computed_shares = $per_share_value * $com['share_amount'];
            $percentage_amount = $percentage_global_share / $computed_shares;
            $this->insertPayout(
                $sponsor_id,
                $sponsor_id,
                $computed_shares,
                $percentage_amount, 
                $computed_shares,
                "Big dog bonus Pool | Member: $sponsor_id has a total of $computed_shares  share",
                0,
                0,
                $sponsor_id
            );
        }


    }

    public function getQualifiedAmbassador()
    {
       
        $end_date   = $this->getPeriodEndDate();        
        $affiliates     = config('commission.member-types.affiliates');

        $sql = "
            SELECT
                u.id AS user_id,
                cdr.paid_as_rank_id,
                u.sponsorid AS sponsor_id,
                cdv.bg5_count,
                cdv.bg9_count,
                cdv.bg10_count,
                cdv.bg11_count,
                cdv.bg12_count
            FROM cm_daily_volumes cdv
            JOIN cm_daily_ranks cdr ON cdr.volume_id = cdv.id AND cdr.rank_date = '$end_date'
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

    private function getBGTotalShares($share, $bg5_count, $bg9_count, $bg10_count, $bg11_count, $bg12_count )
    {  
        $default_share = 1;

        if($share == 2){
            $bg10_count = $bg10_count * $share;
            $bg11_count = $bg11_count * $share;
            $bg12_count = $bg12_count * $share;
            $bg5_count = 0;
        }

        if($share == 4){
            $bg10_count = $bg10_count * $share;
            $bg11_count = $bg11_count * $share;
            $bg12_count = $bg12_count * $share;
            $bg9_count = 0;
        }

        if($share == 6){
            $bg10_count = $bg10_count * $share;
            $bg11_count = $bg11_count * $share;
            $bg12_count = $bg12_count * $share;
            $bg9_count = 0;
        }

        $total_shares = $default_share + $bg5_count + $bg9_count + $bg10_count + $bg11_count + $bg12_count;

        return $total_shares;
    }    

    private function getBGShares($paid_as_rank)
    {  
        $shares = [
              11 => 2,
              12 => 4,
              13 => 6
        ];

        return $shares[$paid_as_rank];
    }

    private function getCompanyGlobalSales()
    {
        $end_date = $this->getPeriodEndDate();
        $start_date = $this->getPeriodStartDate();
        $customer = config('commission.member-types.customer');

        $sql = "SELECT COALESCE(SUM(t.sub_total),0) AS total_amount
            FROM v_cm_transactions t
            WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'                 
                AND t.`type` = 'product'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }
}