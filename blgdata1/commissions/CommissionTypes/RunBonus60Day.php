<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class RunBonus60Day extends CommissionType implements CommissionTypeInterface
{

    const DAYS = 14;
    
    public function count()
    {
        return count($this->getQualifiedUsers());
    }

    public function generateCommission($start, $length)
    {
        $users = $this->getQualifiedUsers($start, $length);

        foreach ($users as $key => $user) {
            $this->log("Processing Payee ID " . $user['user_id']);
            $user_id = $user['user_id'];
            $sponsor_id = $user['sponsor_id'];
            $enrolled_date = $user['enrolled_date'];
            $personally_enrolled = $user['personally_enrolled'];
            $group_volume = $user['gv'];

            $check_if_paid = $this->checkIfPaid($user_id);
            $received14DayBonus = $this->received14DayBonus($user_id);
            $retail_customer_autoship = $this->retailCustomerAutoshipBV($user_id);
            $personal_purchase_autoship = $this->personalAutoshipBV($user_id);

            if(+$received14DayBonus) {
                $amount = 6500; //If the Ambassador has achieved the 14-day bonus and earned the $1000, the user will get the remaining $6,500 bonus.
            }else{
                $amount = 7500;
            }

            if(+$check_if_paid) {
                $this->log(" Payee ID: $user_id already received the $7500 bonus");
                continue;
            }

            if($retail_customer_autoship >= 50 || $personal_purchase_autoship >= 50) {
                $this->insertPayout(
                    $user_id,
                    $user_id,
                    0,
                    100,
                    $amount,
                    "Payee ID: $user_id | Group Volume: $group_volume | Personally Enrolled: $personally_enrolled | Enrolled Date: $enrolled_date",
                    0,
                    0,
                    $sponsor_id
                );
            }else{
                $this->log(" Payee ID: $user_id is not qualified");
                $this->log(" customer pv: $received14DayBonus | personal pv $personal_purchase_autoship");

            }


            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    private function getQualifiedUsers($start = null, $length = null)
    {
        $ambassador = config('commission.member-types.ambasador');
        $end_date = $this->getPeriodEndDate();

       $sql = "SELECT
                dv.user_id,
                s.sponsorid AS sponsor_id,
                COUNT(u.id) AS personally_enrolled,
                dv.gv,
                DATE(s.created) enrolled_date
            FROM cm_daily_volumes dv
            JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
            JOIN users u ON u.sponsorid = dv.user_id
            JOIN users s ON s.id = u.sponsorid
            WHERE dr.is_active = 1 AND dv.gv >= 12000 AND dv.volume_date = '$end_date'
            AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = dv.user_id AND cat_id = $ambassador)
            AND '$end_date' BETWEEN DATE(s.created) AND DATE_ADD(DATE(s.created), INTERVAL 60 DAY) 
            AND u.active = 'Yes' AND u.levelid = 3 -- active personally enrolled
            GROUP BY s.id 
            HAVING COUNT(u.id) >= 8
            ";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function checkIfPaid($user_id)
    {
        $sql = "SELECT COUNT(py.payee_id) FROM cm_commission_payouts py
                JOIN cm_commission_periods cp ON cp.id = py.commission_period_id
                WHERE cp.commission_type_id = 4 AND payee_id = $user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function received14DayBonus($user_id)
    {
        $sql = "SELECT COUNT(py.payee_id) FROM cm_commission_payouts py
                JOIN cm_commission_periods cp ON cp.id = py.commission_period_id
                WHERE cp.commission_type_id = 3 AND payee_id = $user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function retailCustomerAutoshipBV($user_id)
    {
        $customers = config('commission.member-types.customers');
        $end_date = $this->getPeriodEndDate();

        $sql = "SELECT
                    COALESCE(SUM(t.computed_cv), 0)
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
                WHERE t.transaction_date BETWEEN DATE_SUB('$end_date',INTERVAL 1 MONTH) AND '$end_date'
                AND t.type = 'product'
                AND FIND_IN_SET(t.purchaser_catid, '$customers')
                AND t.sponsor_id = $user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function personalAutoshipBV($user_id)
    {
        $ambassador = config('commission.member-types.ambasador');
        $end_date = $this->getPeriodEndDate();

        $sql = "SELECT
                    COALESCE(SUM(t.computed_cv), 0)
                FROM v_cm_transactions t
                JOIN users u ON u.id = t.user_id
                WHERE t.transaction_date BETWEEN DATE_SUB('$end_date',INTERVAL 1 MONTH) AND '$end_date'
                AND t.type = 'product'
                AND FIND_IN_SET(t.purchaser_catid, '$ambassador')
                AND t.user_id = $user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

}