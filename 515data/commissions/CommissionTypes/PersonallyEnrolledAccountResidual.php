<?php

namespace Commissions\CommissionTypes;

use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class PersonallyEnrolledAccountResidual extends CommissionType implements CommissionTypeInterface
{

    public function count()
    {
        return count($this->getQualifiedAccounts());
    }

    public function generateCommission($start, $length)
    {
        $accounts = $this->getQualifiedAccounts($start, $length);

        foreach($accounts as $account) {
            
            $this->log("Processing Personal Energy Account ID " . $account['customer_id']);

            $energy_account_id = $account['energy_account_id'];
            $payee_id = $account['sponsor_id'];
            $sponsor_active_status = $account['sponsor_active_status'];
            $sponsor_system_active_status = $account['sponsor_system_active_status'];
            $customer_id = $account['customer_id'];
            $pea_count = $account['pea_count'];

            if($sponsor_active_status === 0 || $sponsor_system_active_status === 0) {
                $this->log("Associate ID $payee_id is NOT Qualified. Active: $sponsor_active_status. Qualified: $sponsor_system_active_status.");
                $this->log();
                continue;
            }
            
            // Testing purposes
            // $this->forTestingOnly($payee_id, $pea_count);

            //$this->log("Energy Account Status: " . $energy_account_status);
            $this->log("Personal Energy Account Count: " . $pea_count);

            $commission = $this->getBonusAmountByPEACount($pea_count);

            $this->log("Associate ID $payee_id will receive €" . $commission . " as a Personally Enrolled Account Residual");

            $this->insertPayout(
                $payee_id,
                $customer_id,
                $commission,
                100,
                $commission,
                "Associate ID $payee_id earns $commission euros for this personally enrolled flowing energy account.",
                $energy_account_id,
                0,
                $payee_id
            );

        }

        $this->log(); // For progress bar. Put this every end of the loop.
    }

    private function getQualifiedAccounts($start = null, $length = null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $flowing = config('commission.energy-account-status-types.flowing');
        $flowing_pending = config('commission.energy-account-status-types.flowing-pending-cancellation');
        $cancelled = config('commission.energy-account-status-types.cancelled');
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();
        $watt = config('commission.ranks.watt');

        $sql = "
            SELECT 
			    ea.id AS energy_account_id,
			    ea.customer_id, 
			    ea.sponsor_id, 
			    dr.`is_active` AS sponsor_active_status, 
			    dr.`is_system_active` AS sponsor_system_active_status, 
			    dv.pea_flowing AS pea_count
			FROM cm_energy_accounts ea
			JOIN users u ON u.id = ea.sponsor_id
			LEFT JOIN cm_daily_volumes dv ON dv.user_id = u.id AND dv.volume_date = '$end_date'
			JOIN cm_daily_ranks dr ON dr.`volume_id` = dv.`id`
			WHERE 
			    EXISTS (SELECT 1 FROM categorymap WHERE userid = u.id AND FIND_IN_SET(catid, '$affiliates'))
			    AND dv.pea > 0
			    AND EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = ea.id AND FIND_IN_SET(l.current_status, '$flowing,$flowing_pending') AND l.created_date <= '$end_date')
			    AND NOT EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = ea.id AND FIND_IN_SET(l.current_status, '$cancelled') AND l.created_date <= '$end_date')
                AND dr.is_active = 1 AND dr.is_system_active = 1 AND dr.paid_as_rank_id >= $watt
			ORDER BY ea.id
        ";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getBonusAmountByPEACount($count)
    {

        if($count >= 201) $bonus_amount = 1;
        elseif($count >= 101 && $count <= 200) $bonus_amount = 0.75;
        elseif($count >= 25 && $count <= 100) $bonus_amount = 0.60;
        elseif($count >= 1 && $count <= 24) $bonus_amount = 0.50;
        else $bonus_amount = 0;

        return $bonus_amount;
    }

    private function forTestingOnly($user_id, &$pea_count)
    {
        $test = [
            3 => [11],
            4 => [26],
            5 => [20], 
            9 => [18],
            31645 => [75], 
            31644 => [18],  
            31641 => [1],   
            31632 => [5],
            20 => [102],
            34 => [202],
        ];

        if (array_key_exists($user_id, $test)) {
            $pea_count = $test[$user_id][0];
        }

    }


}