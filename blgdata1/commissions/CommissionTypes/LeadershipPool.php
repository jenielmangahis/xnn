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
        $totalGlobalShare     = $this->getCompanyGlobalSales();
        $total_shares = 0;
        $com_data     = array();
        foreach( $qualifiedAmbassadors as $u ){
            $paid_as_rank = $u['paid_as_rank_id'];
            $share        = $this->getBGShares($paid_as_rank);
            $bg5_count    = $u['bg5_count'];               
            $total_shares += $this->getBGTotalShares($share, $bg5_count);
            $com_data[]= [
                'sponsor_id' => $u['sponsor_id'],
                'share_amount' => $this->getBGTotalShares($share, $bg5_count),
            ];
        }

        $percentage_global_share = $totalGlobalShare['total_amount'] * ($percentage/100);
        $per_share_value = $percentage_global_share / $total_shares;

        foreach( $com_data as $com ){
            
            $this->log("Processing leadership pool for sponsor ID " . $com['sponsor_id']);

            $sponsor_id = $com['sponsor_id'];
            $computed_shares = $per_share_value * $com['share_amount'];

            $this->insertPayout(
                $sponsor_id,
                $sponsor_id,
                $computed_shares,
                $percentage,
                $computed_shares,
                "Leadership Pool | Member: $sponsor_id has a total of $computed_shares share",
                0,
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
                cdv.bg5_count
            FROM cm_daily_volumes cdv
            JOIN cm_daily_ranks cdr ON cdr.volume_id = cdv.id 
            JOIN users u ON u.id = cdv.user_id
            WHERE u.active = 'Yes' 
                AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = cdv.user_id AND FIND_IN_SET(a.cat_id,'$affiliates'))
                AND cdr.rank_id IN(8,9,10)
                AND cdr.is_system_active = 1
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

    
    private function getComputedShares($total_shares, $percentage, $company_global_share)
    {  

        $2_percent_global_company_sales = $company_global_share * ($percentage/100);


        $computed_shares = $total_shares + $bg5_count;

        return $computed_shares;
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