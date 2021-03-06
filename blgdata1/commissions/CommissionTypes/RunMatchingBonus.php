<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class RunMatchingBonus extends CommissionType implements CommissionTypeInterface
{
    
    public function count()
    {
        return count($this->getQualifiedAmbassadors());
    }

    public function generateCommission($start, $length)
    {
        $users = $this->getQualifiedAmbassadors($start, $length);

        foreach ($users as $key => $user) {
            $this->log("Processing User ID " . $user['payee_id']);

            $user_id = $user['user_id'];
            $payee_id = $user['payee_id'];
            $sponsor_id = $user['user_id'];
            $is_active = $user['is_active'];

            if($is_active) {
                $this->insertPayout(
                    $payee_id,
                    $user_id,
                    0,
                    100,
                    750,
                    "Payee ID: $payee_id received 60-Day Matching Bonus from User ID: $user_id",
                    0,
                    0,
                    $sponsor_id
                );
            }else{
                $this->log(" Payee ID: $payee_id is not qualified");
            }


            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    private function getQualifiedAmbassadors($start = null, $length = null)
    {
        $end_date = $this->getPeriodEndDate();
        $ambassador = config('commission.member-types.ambasador');

        $sql = "SELECT
                    u.id AS user_id,
                    u.sponsorid AS payee_id,
                    s.sponsorid AS sponsor_id,
                    dr.is_active
                FROM users u
                JOIN users s ON s.id = u.sponsorid
                JOIN cm_commission_payouts py ON py.payee_id = u.id
                JOIN cm_commission_periods cp ON cp.id = py.commission_period_id
                JOIN cm_daily_ranks dr ON dr.user_id = s.id AND dr.rank_date = '$end_date'
                WHERE cp.commission_type_id = 4 AND s.active = 'Yes'
                AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = s.id AND a.cat_id = $ambassador)";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}