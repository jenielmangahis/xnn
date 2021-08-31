<?php

namespace Commissions\CommissionTypes;

use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class CarBonus extends CommissionType implements CommissionTypeInterface
{

    public function count()
    {
        return count($this->getQualifiedUsers());
    }

    public function isSingleProcess()
    {
        return true;
    }

    public function generateCommission($start,$length)
    {
        $users = $this->getQualifiedUsers($start,$length);
        $amount = $this->getBonus();

        foreach($users as $user)
        {
            $user_id = $user['user_id'];
            $sponsor_id = $user['sponsor_id'];
            $rank_name = $user['rank_name'];
            $this->log("Processing User ID $user_id");

            $this->log("User ID $user_id is Qualified for Car Bonus | Maintained Rank : $rank_name");
                $this->insertPayout(
                    $user_id,
                    $user_id,
                    0,
                    100,
                    $amount,
                    "User ID: $user_id received Car Bonus | Maintained Rank : $rank_name",
                    0,
                    0,
                    $sponsor_id
                );
            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    public function getQualifiedUsers($start = null, $length = null)
    {
        $ibo_catid = config('commission.member-types.ibo');
        $end_date = $this->getPeriodEndDate();
        $minimum_bv = 5000;
        $minimum_personal_enrollment = 4;


        $sql = "SELECT
                    dv.user_id,
                    u.sponsorid AS sponsor_id,
                    dr.is_qualified_trader_or_higher,
                    cr.name AS rank_name
                FROM cm_daily_volumes dv
                JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
                JOIN cm_ranks cr ON cr.id = dr.rank_id
                JOIN users u ON u.id = dv.user_id
                WHERE u.active = 'Yes'
                AND dr.is_qualified_trader_or_higher = 1
                AND (dv.group_volume_left_leg >= $minimum_bv AND dv.group_volume_right_leg >= $minimum_bv) -- 10k BV 50% each leg
                AND dv.active_personal_enrollment_count >= $minimum_personal_enrollment
                AND dv.volume_date = '$end_date'
                AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = dv.user_id AND FIND_IN_SET(a.cat_id,'$ibo_catid'))
                ORDER BY dv.user_id";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getBonus()
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $sql = "SELECT bonus_amount FROM cm_car_bonus_settings 
                WHERE start_date BETWEEN '$start_date' AND '$end_date' AND 
                end_date BETWEEN '$start_date' AND '$end_date'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result =  $stmt->fetchColumn();
        $result = $result['bonus_amount'] ? $result['bonus_amount'] : 300;

        return $result;
    }

}