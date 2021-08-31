<?php

namespace Commissions\CommissionTypes;

use Commissions\Contracts\CommissionTypeInterface;

class BinaryCommission extends CommissionType implements CommissionTypeInterface
{
    CONST MINIMUM_LESSER_LEG_VOLUME = 225;
    CONST MINIMUM_RANK_ID = 3; //  IBO

    public function getUsers($start=0, $length=null)
    {
        $q = "SELECT u.fname, u.lname, u.id, v.lesser_volume, r.paid_as_rank_id, c.name as rank_name 
              FROM cm_affiliates a
              JOIN users u 
                  ON u.id=a.user_id    
              JOIN cm_daily_volumes v 
                ON u.id = v.user_id 
              JOIN cm_daily_ranks r 
                ON u.id = r.user_id
              JOIN cm_ranks c 
                ON r.paid_as_rank_id = c.id         
              WHERE u.active = 'Yes'
                  AND EXISTS(
                    SELECT IF(COUNT(1) = 2, 1, 0) FROM cm_genealogy_binary b 
                    WHERE b.parent_id = u.id 
                    GROUP BY b.parent_id               
                  )
                AND v.volume_date = :volume_date
                AND r.rank_date = :rank_date
                AND (v.`total_group_volume_left_leg` * .50 >= 225)
                AND (v.`total_group_volume_right_leg` * .50 >= 225)
                AND (v.`active_personal_enrollment_count` >= 3)
        ";

        if(+$start > 0 && +$length > 0){
            $q .= " LIMIT ". $start. ', '. $length;
        }

        $end_date = $this->getPeriodEndDate();

        $s = $this->db->prepare($q);
        $s->bindParam(':volume_date', $end_date);
        $s->bindParam(':rank_date', $end_date);
        $s->execute();

        return $s->fetchAll(\PDO::FETCH_CLASS);
    }

    public function count()
    {
        return count($this->getUsers());
    }

    public function isSingleProcess()
    {
        return true;
    }

    /**
     * @param $start
     * @param $length
     */
    public function generateCommission($start, $length)
    {
        if($this->count() == 0)
        {
            $this->log('Failed: No qualified users.');
            return;
        }

        $users = $this->getUsers($start, $length);

        foreach($users as $user)
        {
            $commission_value = $user->lesser_volume;
            $paid_as_rank = $user->paid_as_rank_id;

            if($commission_value < self::MINIMUM_LESSER_LEG_VOLUME){
                $this->log('Failed: Minimum volume requirement. Volume is only ' . $commission_value);
                continue;
            }

            if($paid_as_rank < self::MINIMUM_RANK_ID)
            {
                $this->log('Failed: Minimum rank requirement. Rank is is only ' . $user->rank_name);
                continue;
            }
                
			$this->log($commission_value . ' ' . $paid_as_rank);

            $percentage = $this->getPercentage($paid_as_rank);

            if($percentage <= 0)
            {
                $this->log('Failed: commission percentage is zero.');
                continue;
            }

            $amount = $commission_value * ($percentage/100);

            $cap = $this->getEarningCap($paid_as_rank);
            if($amount > $cap)
            {
                $this->log('Info: Amount '. $amount . ' is adjusted to max '. $cap);
                $amount = $cap;
            }
            $remarks = 'Binary Commission: User #'. $user->id. ' with rank '. $user->rank_name .' has earned the amount of '. $amount;

            $this->insertPayout(
                $user->id,
                $user->id,
                $commission_value,
                $percentage,
                $amount,
                $remarks,
                0,
                0,
                $user->id
            );

        }
    }

    private function getPercentage($rank_id)
    {
        switch ($rank_id)
        {
            case config('commission.ranks.ibo'):
                $percentage = 10;
            break;

            case config('commission.ranks.apprentice-trader'):
            case config('commission.ranks.junior-trader'):
            case config('commission.ranks.novice-trader'):
            case config('commission.ranks.qualified-trader'):
            case config('commission.ranks.team-trader'):
            case config('commission.ranks.national-trader'):
            case config('commission.ranks.international-trader'):
                $percentage = 15;
            break;

            case config('commission.ranks.world-trader'):
                $percentage = 18;
            break;


            case config('commission.ranks.global-trader'):
                $percentage = 20;
            break;

            default:
                $percentage = 0;
            break;


        }
        return $percentage;
    }

    private function getEarningCap($rank_id)
    {
        switch ($rank_id)
        {
            case config('commission.ranks.ibo'):
                $max = 500;
                break;

            case config('commission.ranks.apprentice-trader'):
                $max = 1500;
                break;

            case config('commission.ranks.junior-trader'):
                $max = 3000;
                break;

            case config('commission.ranks.novice-trader'):
                $max = 5000;
                break;

            case config('commission.ranks.qualified-trader'):
                $max = 10000;
                break;

            case config('commission.ranks.team-trader'):
                $max = 25000;
                break;

            case config('commission.ranks.national-trader'):
                $max = 50000;
                break;

            case config('commission.ranks.international-trader'):
                $max = 200000;
                break;

            case config('commission.ranks.world-trader'):
                $max = 500000;
                break;


            case config('commission.ranks.global-trader'):
                $max = 1000000;
                break;

            default: $max = 0;
        }

        return $max;

    }
}