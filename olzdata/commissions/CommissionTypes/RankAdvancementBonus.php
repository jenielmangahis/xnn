<?php

namespace Commissions\CommissionTypes;

use App\Transaction;
use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RankAdvancementBonus extends CommissionType implements CommissionTypeInterface
{
    const MINIMUM_RANK = 2;
    const MAXIMUM_RANK = 9;

    public function count()
    {
        return count($this->getUsers());
    }

    public function generateCommission($start, $length)
    {
        $users = $this->getUsers($start, $length);    

        if(count($users) > 0) {

            //delete cm_rab_payoouts
             // delete first
            $period_id = $this->getPeriodId();
            DB::table('cm_rab_payouts')->where('commission_period_id', '=', $period_id)->delete();

            foreach($users as $user) {
                
                $user_id = $user['user_id'];
                $rank_id = $user['rank_id'];
                if($rank_id >= self::MINIMUM_RANK && $rank_id <= self::MAXIMUM_RANK) {

                    $flag = $this->checkPreviousRank($user_id, $rank_id);
                    
                    if(!$flag) {
                        $commission_value = $this->getUserBonus($rank_id);
                        
                        $this->insertRabPayouts($user);

                        $this->log("User:$user_id with Rank ID:$rank_id has earned a Rank Advancement Bonus of $".$commission_value);

                        $this->insertPayout(
                            $user_id,
                            $user_id,
                            0,
                            0,
                            $commission_value,
                            "User:$user_id with Rank ID:$rank_id has earned a Rank Advancement Bonus of $".$commission_value,
                            0,
                            0,
                            $user['sponsorid']
                        );
                    }
                }
            }
        }
    }

    public function insertRabPayouts($data)
    {
        DB::table('cm_rab_payouts')->insert([
            'user_id' => $data['user_id'],
            'rank_id' => $data['rank_id'],
            'commission_period_id' => $this->getPeriodId(),
            'date_achieved' => $data['date_achieved']
        ]);
    }

    public function checkPreviousRank($user_id, $rank_id)
    {
        $sql = "
            SELECT crp.* FROM cm_rab_payouts AS crp 
            WHERE crp.user_id = $user_id AND crp.rank_id = $rank_id
            AND EXISTS(SELECT 1 FROM cm_commission_periods ccp WHERE crp.commission_period_id = ccp.id AND ccp.is_locked = 1)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
     
        $flag = false;
        if(count($result) > 0) {
            $this->log("User:$user_id already achieved Rank ID:$rank_id and earned Rank Advancement Bonus");

            $flag = true;
        }

        return $flag;
    }

    public function getUserBonus($rank_id)
    {
        $bonuses = [
            2 => 50,
            3 => 50,
            4 => 100,
            5 => 100,
            6 => 100,
            7 => 500,
            8 => 750,
            9 => 1000
        ];

        return $bonuses[+$rank_id];
    }

    public function getUsers($start=null, $length=null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $sql = "
            SELECT car.user_id, car.rank_id, car.date_achieved, u.sponsorid FROM cm_achieved_ranks car
            JOIN users u ON u.id = car.user_id
            WHERE car.date_achieved BETWEEN '$start_date' AND '$end_date'
            AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = car.user_id AND FIND_IN_SET(a.cat_id,'$affiliates'))
            AND u.active = 'yes'
            AND car.rank_id > 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function isQualifiedForRankAdvancementBonus($user_id) {

        $affiliates = config('commission.member-types.affiliates');

        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');

        $sql = "
            SELECT car.user_id, car.rank_id, car.date_achieved, u.sponsorid FROM cm_achieved_ranks car
            JOIN users u ON u.id = car.user_id
            WHERE car.date_achieved BETWEEN '$start_date' AND '$end_date'
            AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = car.user_id AND FIND_IN_SET(a.cat_id,'$affiliates'))
            AND u.active = 'yes'
            AND car.rank_id > 1
            AND u.id = $user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $isQualified = false;
        
        if(count($result) > 0) {
            foreach($result as $user) {
                
                if($user['rank_id'] >= self::MINIMUM_RANK && $user['rank_id'] <= self::MAXIMUM_RANK) {

                    $flag = $this->checkPreviousRank($user['user_id'], $user['rank_id']);
                    
                    if(!$flag) {
                        $isQualified = true;
                    }
                }
            }
        }

        return $isQualified;
    }
}