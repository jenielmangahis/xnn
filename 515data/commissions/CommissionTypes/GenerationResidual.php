<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 11/23/2020
 * Time: 7:25 PM
 */

namespace Commissions\CommissionTypes;


use App\CommissionPeriod;
use App\DailyRank;
use App\EnergyAccount;
use Commissions\BackgroundWorkerLogger;
use Commissions\Contracts\CommissionTypeInterface;
use Commissions\Repositories\PayoutRepository;
use Illuminate\Support\Facades\DB as DB;

class GenerationResidual  extends CommissionType implements CommissionTypeInterface
{
    protected $bonus_percentage;

    public function count(){
        return count($this->getQualifiedGenerations());
    }

    public function isSingleProcess()
    {
        return false;
    }

    public function generateCommission($start, $length)
    {
        $generations = $this->getQualifiedGenerations($start, $length);

        foreach ($generations as $generation) {

            $payee_id = $generation['payee_id'];
            $payee_rank = $generation['payee_rank_name'];
            $payee_rank_id = $generation['payee_rank_id'];
            $purchaser_id = $generation['customer_id'];
            $generation_sponsor_id = $generation['generation_parent_id'];
            $generation_level = $generation['generation'];
            $generation_member_rank_id = $generation['generation_parent_rank_id'];
            $generation_member_rank = $generation['generation_parent_rank_name'];
           // $order_type = $generation['is_customer'] ? 'Customer' : 'Associate';
            $sponsor_id = $generation['parent_id'];
            $energy_account_id = $generation['order_id'];

            //$this->log("Processing ID $purchaser_id.");
            $this->log("Member ID $payee_id has a Paid As Title of $payee_rank and qualified.");


            $commission = $this->getAmountPerGeneration($generation_level,$payee_rank_id); // per user para ang summary na magsum sa total payout

            if($commission > 0) {

                $this->insertPayout(
                    $payee_id,
                    $purchaser_id,
                    0,
                    0,
                    $commission,
                    "Generation Residual for ID $payee_id ($payee_rank) from Generation ID $generation_sponsor_id ($generation_member_rank).",
                    $energy_account_id,
                    $generation_level,
                    $generation['sponsor_id']
                );

                $this->log("Member ID $payee_id receives $$commission Generation Residual as a $payee_rank.");
            }

            $this->log(); // For progress bar. Put this every end of the loop.

        }

    }

    private function getAmountPerGeneration($generation_level,$rank_id) // kuhaon ang payout amount base sa rank
    {
        $r = config('commission.ranks');

        $percentages = [
            $r['global-leader'] => [0.07, 0, 0, 0],
            $r['president-leader'] => [0.08, 0.07, 0, 0],
            $r['ceo-leader'] => [0.1, 0.08, 0.07, 0],
            $r['founding-leader'] => [0.1, 0.1, 0.08, 0.07],
        ];

        if (!array_key_exists($rank_id, $percentages)) return 0;

        if (!array_key_exists($generation_level - 1, $percentages[$rank_id])) return 0;

        return $percentages[$rank_id][$generation_level - 1];
    }

    private function getQualifiedGenerations($start = null, $length = null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();
        $affiliates = config('commission.member-types.affiliates');
        $flowing = config('commission.energy-account-status-types.flowing');
        $flowing_pending = config('commission.energy-account-status-types.flowing-pending-cancellation');
        $cancelled = config('commission.energy-account-status-types.cancelled');
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, LEVEL, `generation`, generation_parent_id, is_customer, is_customer_customer, root_id) AS (
            SELECT
                u.id AS user_id,
                sponsorid AS parent_id,
                0 LEVEL,
                0 generation,
                0 AS generation_parent_id,
                0 IS NULL is_customer,
                0 is_customer_customer,
                u.id root_id
            FROM cm_daily_ranks dr
            JOIN cm_ranks r ON dr.paid_as_rank_id = r.id
            JOIN users u ON u.id = dr.user_id
            WHERE dr.paid_as_rank_id >= 9 AND dr.is_active = 1 AND dr.is_system_active = 1 AND dr.rank_date = '$end_date'
            UNION ALL
            
            SELECT
                p.id AS user_id,
                p.sponsorid AS parent_id,
                downline.level + 1,
                IF(dr.paid_as_rank_id >= 8, downline.`generation` + 1, downline.`generation`)  AS `generation`,
                IF(dr.paid_as_rank_id >= 8, p.id, downline.generation_parent_id) AS generation_parent_id,
                dr.id IS NULL is_customer,
                downline.is_customer AND dr.id IS NULL is_customer_customer,
                downline.root_id
            FROM users p    
            JOIN downline ON p.sponsorid = downline.user_id
            JOIN cm_daily_ranks dr ON dr.user_id = p.id AND dr.rank_date = '$end_date'
            WHERE p.levelid = 3
            )
            SELECT 
            d.root_id AS payee_id, 
            r1.id AS payee_rank_id, 
            r1.name AS payee_rank_name, 
            d.user_id, d.parent_id, 
            r.id AS rank_id, 
            r.name AS rank_name, 
            d.level, generation,
            generation_parent_id, 
            r.id AS generation_parent_rank_id, 
            r.name AS generation_parent_rank_name,
            ea.id AS order_id,
            ea.sponsor_id, ea.customer_id
            FROM downline d
            JOIN cm_energy_accounts ea ON ea.sponsor_id = d.user_id
            -- JOIN cm_energy_account_status_types et ON et.id = ea.status
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = d.generation_parent_id AND dr.rank_date = '$end_date'
            LEFT JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
            LEFT JOIN cm_daily_ranks dr1 ON dr1.user_id = d.root_id AND dr1.rank_date = '$end_date'
            LEFT JOIN cm_ranks r1 ON r1.id = dr1.paid_as_rank_id
            WHERE generation > 0
           -- AND EXISTS(SELECT 1 FROM categorymap WHERE userid = d.root_id AND FIND_IN_SET(catid,'$affiliates'))
            AND EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = ea.id AND FIND_IN_SET(l.current_status, '$flowing,$flowing_pending') AND l.created_date <= '$end_date')
            AND NOT EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = ea.id AND FIND_IN_SET(l.current_status, '$cancelled') AND l.created_date <= '$end_date')
            -- ORDER BY d.root_id, d.level, generation
            ORDER BY ea.id, generation
        ";

        if ($start !== null) {
            $sql .= " LIMIT $start, $length";
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}