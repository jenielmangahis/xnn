<?php

namespace Commissions\CommissionTypes;

use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;
use App\RankConsistency AS RC;

class RankConsistency extends CommissionType implements CommissionTypeInterface
{
    public function count()
    {
        return count($this->getQualifiedUsers());
    }

    public function isSingleProcess()
    {
        return true;
    }

    public function beforeCommissionRun()
    {
        RC::ofPeriod($this->getPeriodId())->delete();
    }

    public function generateCommission($start,$length)
    {
        $users = $this->getQualifiedUsers($start,$length);

        foreach($users as $user)
        {
            $user_id = $user['user_id'];
            $sponsor_id = $user['sponsor_id'];
            $rank_id = +$user['rank_id'];
            $rank_name = $user['rank_name'];

            $amount = $this->getBonus($rank_id);

            $this->log("User ID: $user_id maintained rank $rank_name for 90 days");

            if($this->hasReceivedBonus($user_id,$rank_id))
            {
                $this->log("User ID: $user_id already received the bonus for $rank_name rank");
            }else{
                $this->insertPayout(
                    $user_id,
                    $user_id,
                    0,
                    100,
                    $amount,
                    "Rank ID: $rank_id received Rank Consistency Bonus",
                    0,
                    0,
                    $sponsor_id
                );

                $this->addReceivedBonus(
                    [
                        'user_id' => $user_id,
                        'commission_period_id' => $this->getPeriodId(),
                        'rank_id' => $rank_id,
                        'is_received' => 1
                    ]);
            }

            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    public function getQualifiedUsers($start = null, $length = null)
    {
        $end_date = $this->getPeriodEndDate();
        $sql = "
            SELECT 
                user_id,
                u.sponsorid AS sponsor_id,
                rank_id,
                r.name AS rank_name,
                COUNT(rank_id) AS maintainedMonths
            FROM (
                SELECT 
                *
                FROM cm_daily_ranks cdr
                WHERE cdr.`rank_id` >= 4 -- minimum rank
                AND cdr.`rank_date` = LAST_DAY(cdr.`rank_date`)
                AND cdr.`rank_date` BETWEEN DATE_SUB('$end_date', INTERVAL 90 DAY) AND '$end_date'
                -- AND cdr.is_active = 1 AND cdr.is_system_active = 1
                GROUP BY LAST_DAY(cdr.`rank_date`)
            ) AS ranks
            JOIN users u ON u.id = ranks.user_id
            JOIN cm_ranks r ON r.id = ranks.rank_id
            -- GROUP BY ranks.rank_id
            HAVING maintainedMonths >= 3
                ";

//        if ($start !== null) {
//            $sql .= " LIMIT {$start}, {$length}";
//        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getBonus($rank_id)
    {
        $bonus_amount =
            [
                config('commission.ranks.team-leader')           => 300,
                config('commission.ranks.sr-team-leader')        => 400,
                config('commission.ranks.exec-team-leader')      => 500,
                config('commission.ranks.manager')               => 1500,
                config('commission.ranks.sr-manager')            => 2000,
                config('commission.ranks.director')              => 5000
            ];
        return $bonus_amount[$rank_id];
    }

    public function addReceivedBonus($data)
    {
        DB::table('cm_rank_consistency')->insert($data);

    }

    public function hasReceivedBonus($user_id,$rank_id)
    {
        $sql = "SELECT
                  COUNT(user_id) c
                FROM cm_rank_consistency 
                WHERE user_id = $user_id AND rank_id >= $rank_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return +$stmt->fetchColumn() > 0;
    }

}