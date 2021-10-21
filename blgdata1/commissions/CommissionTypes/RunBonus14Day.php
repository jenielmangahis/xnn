<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class RunBonus14Day extends CommissionType implements CommissionTypeInterface
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
            $check_if_paid = $this->checkIfPaid($user_id);

            if(+$check_if_paid) {
                $this->log(" Payee ID: $user_id already received the $1000 bonus");
            }else{
                $this->insertPayout(
                    $user_id,
                    $user_id,
                    0,
                    100,
                    1000,
                    "Payee ID: $user_id | Personally Enrolled: $personally_enrolled | Enrolled Date: $enrolled_date",
                    0,
                    0,
                    $sponsor_id
                );
            }



            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    private function getQualifiedUsers($start = null, $length = null)
    {
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
            WHERE dr.is_active = 1 AND dv.gv >= 1700 AND dv.volume_date = '$end_date'
            AND '$end_date' BETWEEN DATE(s.created) AND DATE_ADD(DATE(s.created), INTERVAL 14 DAY) 
            AND u.active = 'Yes' -- active personally enrolled
            GROUP BY s.id 
            HAVING COUNT(u.id) >= 4
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
                WHERE cp.commission_type_id = 3 AND payee_id = $user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

}