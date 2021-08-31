<?php

namespace Commissions\CommissionTypes;
use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;
use App\RankIncentives as RI;

class RankIncentives extends CommissionType implements CommissionTypeInterface
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
        RI::ofPeriod($this->getPeriodId())->delete();
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

            //$this->log("Processing User ID $user_id");
            //testing
            //$this->forTestingOnly($user_id,$rank_id,$rank_name);
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
                    "Rank ID: $rank_id received Rank Incentive bonus",
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
                WHERE cdr.`rank_id` >= 5 -- minimum rank
                AND cdr.`rank_date` = LAST_DAY(cdr.`rank_date`)
                AND cdr.`rank_date` BETWEEN DATE_SUB('$end_date', INTERVAL 90 DAY) AND '$end_date'
                AND cdr.is_active = 1 AND cdr.is_system_active = 1
                GROUP BY LAST_DAY(cdr.`rank_date`)
            ) AS ranks
            JOIN users u ON u.id = ranks.user_id
            JOIN cm_ranks r ON r.id = ranks.rank_id
            GROUP BY ranks.rank_id
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
                config('commission.ranks.junior-trader')              => 500,
                config('commission.ranks.novice-trader')              => 1000,
                config('commission.ranks.qualified-trader')           => 2500,
                config('commission.ranks.team-trader')                => 5000,
                config('commission.ranks.national-trader')            => 10000,
                config('commission.ranks.international-trader')       => 25000,
                config('commission.ranks.world-trader')               => 50000,
                config('commission.ranks.global-trader')              => 100000
            ];
        return $bonus_amount[$rank_id];
    }

    public function addReceivedBonus($data)
    {
        DB::table('cm_rank_incentives')->insert($data);

    }

    public function hasReceivedBonus($user_id,$rank_id)
    {
        $sql = "SELECT
                  COUNT(user_id) c
                FROM cm_rank_incentives 
                WHERE user_id = $user_id AND rank_id >= $rank_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return +$stmt->fetchColumn() > 0;
    }

    private function forTestingOnly($user_id, &$rank_id,&$rank_name)
    {
        $test = [
            3 => [5,'Junior Trader'],
            20 => [4],
        ];

        if(array_key_exists($user_id, $test)) {
            $rank_id = $test[$user_id][0];
            $rank_name = $test[$user_id][1];
        }

    }
}