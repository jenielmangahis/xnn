<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class MonthlyLevelCommission extends CommissionType implements CommissionTypeInterface
{
    const MINIMUM_RANK = 2;
    private $user_percentage = [];

    public function count()
    {
        return count($this->getQualifiedUsers());
    }

    public function generateCommission($start, $length)
    {
        $users = $this->getQualifiedUsers($start, $length);
        $this->setUserPercentage();
        
        foreach($users as $user) {
            $userDownlines = $this->getDownlines($user['user_id']);

            foreach($userDownlines as $downline) {
                if(+$user['paid_as_rank_id'] > 1) {

                    $flag = $this->evaluateUserDownline($user['paid_as_rank_id'], $downline);
                    if($flag) {
                        $percentage = $this->getUserPercentage($user['paid_as_rank_id'], $downline['level']);

                        $payee_id = $user['user_id'];
                        $user_id = $downline['user_id'];
                        $commission_value = $downline['cv'];
                        $transaction_id = $downline['order_id'];
                        $level = $downline['level'];
                        $sponsor_id = $downline['sponsor_id'];
                        
                        $this->insertPayout(
                            $payee_id,
                            $payee_id,
                            $commission_value,
                            $percentage,
                            $commission_value * ($percentage / 100),
                            " $payee_id has earned a commission on level $level",
                            0,
                            $level,
                            $sponsor_id
                        );
                    }
                }
            }
        }
    }

    private function getQualifiedUsers($start = null, $length = null)
    {
        $affiliates = config('commission.member-types.affiliates');
        $end_date = $this->getPeriodEndDate();
        $min_rank = self::MINIMUM_RANK;

        $sql = "SELECT
                u.id AS user_id,
                dr.paid_as_rank_id,
                r.name AS current_rank,
                u.sponsorid AS sponsor_id
            FROM cm_daily_volumes dv
            JOIN cm_daily_ranks dr ON dr.volume_id = dv.id AND dr.rank_date = '$end_date'
            JOIN users u ON u.id = dv.user_id
            JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
            WHERE u.active = 'Yes' 
            AND EXISTS(SELECT 1 FROM cm_affiliates a WHERE a.user_id = dv.user_id AND FIND_IN_SET(a.cat_id,'$affiliates'))
            AND dr.paid_as_rank_id >= $min_rank";


        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDownlines($user_id)
    {
        $customers = config('commission.member-types.customers');
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                SELECT 
                id AS user_id,
                sponsorid AS parent_id,
                1 AS `level`
                FROM users
                WHERE sponsorid = $user_id AND levelid = 3
                
                UNION ALL
                
                SELECT
                p.id AS user_id,
                p.sponsorid AS parent_id,
                downline.`level` + 1 `level`
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                WHERE p.levelid = 3 AND p.active = 'Yes'
            )
            SELECT 
                t.transaction_id AS order_id, 
                t.user_id,
                t.sponsor_id AS sponsor_id,
                COALESCE(t.computed_cv, 0) AS cv,
                t.transaction_date,
                d.level
            FROM downline d 
            JOIN v_cm_transactions t ON t.user_id = d.user_id
            WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND t.`type` = 'product' 
                AND t.sub_total > 0
                AND t.computed_cv > 0
                AND d.level <= 5
	            GROUP BY d.level
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);

    }

    private function evaluateUserDownline($paid_as_rank, $downline)
    {
        $flag = false;
        if($paid_as_rank === 2 && $downline['level'] === 1) {
            $flag = true;
        }
        elseif($paid_as_rank === 3 && $downline['level'] >= 1 && $downline['level'] <= 2) {
            $flag = true;
        }
        elseif($paid_as_rank === 4 && $downline['level'] >= 1 && $downline['level'] <= 2) {
            $flag = true;
        }
        elseif($paid_as_rank === 5 && $downline['level'] >= 1 && $downline['level'] <= 2) {
            $flag = true;
        }
        elseif($paid_as_rank === 6 && $downline['level'] >= 1 && $downline['level'] <= 3) {
            $flag = true;
        }
        elseif($paid_as_rank === 7 && $downline['level'] >= 1 && $downline['level'] <= 4) {
            $flag = true;
        }
        elseif($paid_as_rank === 8 && $downline['level'] >= 1 && $downline['level'] <= 5) {
            $flag = true;
        }
        elseif($paid_as_rank === 9 && $downline['level'] >= 1 && $downline['level'] <= 5) {
            $flag = true;
        }

        if(!$flag) {
            $this->log("Skip Order:". $downline['order_id']. ", downline of:".$downline['sponsor_id'].", level:".$downline['level']);
        }

        return $flag;
    }

    private function setUserPercentage()
    {
        $this->user_percentage = [
            "sr_representative" => [
                "level_1" => 5,
            ],
            "leader" => [
                "level_1" => 6,
                "level_2" => 3
            ],
            "team_leader" => [
                "level_1" => 8,
                "level_2" => 5
            ],
            "sr_team_leader" => [
                "level_1" => 9,
                "level_2" => 6
            ],
            "exec_team_leader" => [
                "level_1" => 9,
                "level_2" => 7,
                "level_3" => 2,
            ],
            "manager" => [
                "level_1" => 10,
                "level_2" => 8,
                "level_3" => 4,
                "level_4" => 1,
            ],
            "sr_manager" => [
                "level_1" => 10,
                "level_2" => 8,
                "level_3" => 5,
                "level_4" => 2,
                "level_5" => 1,
            ],
            "director" => [
                "level_1" => 10,
                "level_2" => 8,
                "level_3" => 5,
                "level_4" => 3,
                "level_5" => 2,
            ],
        ];
    }

    private function getUserPercentage($rank, $level)
    {
        switch ($rank) {
            case 2:
                $percentage = $this->user_percentage['sr_representative']['level_'.$level];
                break;
            case 3:
                $percentage = $this->user_percentage['leader']['level_'.$level];
                break;
            case 4:
                $percentage = $this->user_percentage['team_leader']['level_'.$level];
                break;
            case 5:
                $percentage = $this->user_percentage['sr_team_leader']['level_'.$level];
                break;
            case 6:
                $percentage = $this->user_percentage['exec_team_leader']['level_'.$level];
                break;
            case 7:
                $percentage = $this->user_percentage['manager']['level_'.$level];
                break;
            case 8:
                $percentage = $this->user_percentage['sr_manager']['level_'.$level];
                break;
            case 9:
                $percentage = $this->user_percentage['director']['level_'.$level];
                break;
            default:
                $percentage = $this->user_percentage['representative']['level_1'];
                break;
        }

        return $percentage;
    }
}