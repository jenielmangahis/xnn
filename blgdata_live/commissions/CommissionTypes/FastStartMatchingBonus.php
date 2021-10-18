<?php

namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;

class FastStartMatchingBonus extends CommissionType implements CommissionTypeInterface
{
    public function count()
    {
        return count($this->getUsersFSBEarnings());
    }

    public function generateCommission($start, $length)
    {
        $qualifiedMembers = $this->getUsersFSBEarnings($start, $length);

        if(count($qualifiedMembers) > 0) {
            foreach ($qualifiedMembers as $member) {
            
                $user_uplines = $this->getUplines($member['user_id']);

                if($member['amount_earned'] > 0) {

                    if(count($user_uplines) >= 1) { // level 1
                        foreach($user_uplines as $upline) {

                            if($upline['level'] == 0) {
                                continue;
                            }

                            if($upline['is_active'] == 0 || $upline['is_system_active'] == 0) {

                                $this->log('Level - '.$upline['level'].' Upline '. $upline['user_id'] .' is in-active and not qualified to recieve Matching Bonus');
                                continue; // next upline // compression
                            }

                            $percentage = $this->getPercentage($upline['level']);

                            $this->insertPayout(
                                $upline['user_id'],
                                $member['user_id'],
                                0,
                                $percentage,
                                $member['amount_earned'] * ($percentage / 100),
                                "Level - ".$upline['level']." Upline ID: ".$upline['user_id']." received Fast Start Matching Bonus from ".$member['user_id'],
                                0,
                                $upline['level'],
                                $upline['user_id']
                            );
                        }
                    }
                    else {
                        $this->log('No upline found for ' . $member['user_id']);
                        continue; // move onto next member
                    }
                }
    
                $this->log(); // For progress bar. Put this every end of the loop.
            }
        }
        else {
            $this->log("No Qualified Members. ".$this->getPeriodStartDate()."-".$this->getPeriodEndDate()." Fast Start Bonus must be lock fist!");
        }
    }

    private function getPercentage($level)
    {
        $percentage = 0;
        switch ($level) {
            case 1:
                $percentage = 15;
                break;
            case 2:
                $percentage = 10;
                break;
            case 3:
                $percentage = 5;
                break;
            default:
                $percentage = 0;
                break;
        }

        return $percentage;
    }

    private function getUplines($user_id)
    {
        $rank_date = $this->getPeriodEndDate();
        $q = $this->db->prepare(
            "WITH RECURSIVE upline AS (
                SELECT 
                        user_id,
                        sponsor_id AS parent_id,
                        0 AS `level`
                    FROM cm_genealogy_placement
                       WHERE user_id = $user_id
                       AND is_placed = 1 
                    UNION ALL
                    
                    SELECT
                        p.user_id,
                        p.sponsor_id AS parent_id,
                        upline.`level` + 1 `level`
                    FROM cm_genealogy_placement p
                    INNER JOIN upline ON p.user_id = upline.parent_id
                    WHERE p.user_id <> upline.user_id
                   AND p.is_placed = 1
                )
                SELECT u.*,
                    dr.is_active,
                    dr.is_system_active
                FROM upline u
                JOIN cm_daily_ranks dr ON u.user_id = dr.user_id
                WHERE u.level <=3 AND dr.rank_date = '$rank_date'
                ORDER BY u.level ASC
            "
        );

        $q->execute();

        return $q->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getUsersFSBEarnings($start = null, $length = null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $fsb_id = config('commission.commission-types.fast-start-bonus');

        $sql = "SELECT SUM(py.amount) AS amount_earned, py.sponsor_id AS user_id 
                FROM cm_commission_payouts py
                JOIN cm_commission_periods p ON py.commission_period_id = p.id
                JOIN cm_commission_types t ON t.id = p.commission_type_id
                WHERE p.commission_type_id = $fsb_id AND p.is_locked = 1
                    AND p.start_date = '$start_date' AND p.end_date = '$end_date'
                GROUP BY py.sponsor_id
                ORDER BY py.sponsor_id ASC";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}