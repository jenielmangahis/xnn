<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;
use Carbon\Carbon;

class MonthlyImmediateEarningsTrueUp extends CommissionType implements CommissionTypeInterface
{

    public function count()
    {
        return count($this->getQualifiedAssociates());
    }

    public function generateCommission($start, $length)
    {
        $qualified_associates = $this->getQualifiedAssociates($start, $length);

        foreach ($qualified_associates as $associate) {

            $payee_id = +$associate['user_id'];
            $months = explode(",", $associate['months']);

            foreach ($months as $month) {
                $energy_accounts = $this->getApprovedEnergyAccounts($payee_id, $month);

                $approved_count = count($energy_accounts);

                $bonus = $this->getBonus($approved_count);

                if($bonus === 0) {
                    $this->log("Member ID $payee_id does not achieved the minimum number of approved account for period $month");
                    continue;
                };

                $this->log("Member ID $payee_id's approved count for $month: $approved_count");

                foreach ($energy_accounts as $account) {

                    $energy_account_id = $account['energy_account_id'];
                    $user_id = $account['user_id'];
                    $generated_bonus = +$account['generated_bonus'];
                    $reference_id = $account['reference_id'];
                    $enrolled_date = $account['enrolled_date'];
                    $approved_date = $account['approved_date'];

                    $amount = $bonus - $generated_bonus;

                    if($amount <= 0) continue;

                    $this->log("Member ID $payee_id earns $amount from Member ID $user_id (Reference ID: $reference_id, Enrolled: $enrolled_date, Approved: $approved_date)");

                    $this->insertPayout(
                        $payee_id,
                        $user_id,
                        $amount,
                        100,
                        $amount,
                        "Approved Count: $approved_count, Previous Bonus: $generated_bonus, Reference ID: $reference_id, Enrolled: $enrolled_date, Approved: $approved_date",
                        $energy_account_id,
                        1,
                        $payee_id
                    );

                }

            }

            $this->log();
        }
    }

    private function getBonus($approved_count)
    {
        if($approved_count >= 16) return 15;
        elseif($approved_count >= 11) return 10;
        elseif($approved_count >= 7) return 5;
        else return 0;
    }


    private function getApprovedEnergyAccounts($user_id, $month)
    {
        $end_date = $this->getPeriodEndDate();
        $start_date = Carbon::createFromFormat("Y-m-d", $month)->startOfMonth()->format("Y-m-d");
        $status = 4;
		$cancelled = config('commission.energy-account-status-types.cancelled');

        $sql = "
            SELECT 
                a.customer_id AS user_id,
                IFNULL(SUM(p.amount), 0) generated_bonus,
                DATE(a.created_at) enrolled_date,
                l.created_date approved_date,
                a.reference_id,
                a.id energy_account_id
            FROM cm_energy_account_logs l
            JOIN cm_energy_accounts a ON a.id = l.energy_account_id
            LEFT JOIN cm_commission_payouts p ON p.transaction_id = a.id 
                AND EXISTS(SELECT 1 FROM cm_commission_periods pr WHERE pr.end_date < '$end_date' AND pr.commission_type_id = 2 AND pr.id = p.commission_period_id)
            WHERE l.created_date BETWEEN '$start_date' AND '$end_date'
                AND l.current_status = $status
                AND l.id = (SELECT l1.id FROM cm_energy_account_logs l1 WHERE l1.energy_account_id = a.id AND l1.current_status = $status ORDER BY l1.created_at ASC LIMIT 1) -- first time approved
                AND a.sponsor_id = $user_id
                AND NOT EXISTS (SELECT 1 FROM cm_energy_account_logs lc WHERE lc.energy_account_id = a.id AND FIND_IN_SET(lc.current_status, '$cancelled') AND lc.created_date <= '$end_date')
                AND NOT FIND_IN_SET(a.id,getFirst3ApprovedAccount(a.sponsor_id))
                AND LAST_DAY(DATE(a.created_at)) = '$month'
            GROUP BY a.id -- changed from customer_id to a.id (by jen 20210326)
            -- HAVING generated_bonus < 15
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getQualifiedAssociates($start = null, $length = null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $status = 4;
        $watt = config('commission.ranks.watt');
        $cancelled = config('commission.energy-account-status-types.cancelled');
        $sql = "
            SELECT
                dr.user_id,
                GROUP_CONCAT(DISTINCT DATE(LAST_DAY(a.created_at)) ORDER BY a.created_at) months
            FROM cm_daily_ranks dr
            JOIN cm_energy_accounts a ON a.sponsor_id = dr.user_id
            JOIN cm_energy_account_logs l ON l.energy_account_id = a.id AND l.created_date BETWEEN '$start_date' AND '$end_date'
            WHERE dr.is_active = 1 -- is qualfied
                AND dr.is_system_active = 1
                AND dr.paid_as_rank_id >= $watt
                AND dr.rank_date = '$end_date'
                AND l.current_status = $status
                AND l.id = (SELECT l1.id FROM cm_energy_account_logs l1 WHERE l1.energy_account_id = a.id AND l1.current_status = $status ORDER BY l1.created_at ASC LIMIT 1) -- first time approved
                AND NOT EXISTS (SELECT 1 FROM cm_energy_account_logs lc WHERE lc.energy_account_id = a.id AND FIND_IN_SET(lc.current_status, '$cancelled') AND lc.created_date <= '$end_date')
                AND 
                (
                    NOT EXISTS(
                        SELECT 1
                          FROM cm_commission_payouts ccp 
                          JOIN cm_energy_accounts cea2 ON cea2.id = ccp.transaction_id
                          JOIN cm_commission_periods ccp1 ON ccp1.id = ccp.commission_period_id
                         WHERE ccp1.is_locked = 1 AND ccp1.commission_type_id = 2 AND cea2.reference_id = a.reference_id
                     )
                     OR 
                     EXISTS(SELECT 1 FROM cm_energy_account_logs lc WHERE lc.energy_account_id = a.id AND FIND_IN_SET(lc.current_status, '$cancelled') 
                     AND lc.created_date <= DATE_SUB('$end_date', INTERVAL 6 MONTH)) -- canceled accounts more than 6 months ago
                )
            GROUP BY dr.user_id
            ORDER BY dr.user_id ASC
        ";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}