<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 11/23/2020
 * Time: 7:25 PM
 */

namespace Commissions\CommissionTypes;


use App\DailyRank;
use App\EnergyAccount;
use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class UnilevelResidual  extends CommissionType implements CommissionTypeInterface
{
    public function count()
    {
        return count($this->getAccounts());
    }

    public function generateCommission($start, $length)
    {
        $this->log('Getting accounts... ');

        $accounts = $this->getAccounts($start, $length);
        foreach($accounts as $account){
            $this->log(json_encode($account));
            $this->log(' Evaluating account '. $account['customer_id']);

            $uplines = $this->getUserUpline($account['sponsor_id']);
            if(count($uplines) <= 0) $this->log('No uplines found');
            $level = 1;

            foreach($uplines as $upline){
                if($upline['user_id'] == $account['sponsor_id']) continue; // level 0
//
//                if(+$upline['is_active']==0 || +$upline['is_system_active']==0)
//                {
//                    $this->log($upline['fname'] .' '. $upline['lname'] . ' must be active and qualified.');
//                    continue;
//                }

                if(+$upline['paid_as_rank_id'] >= config('commission.ranks.watt') && +$upline['is_active'] == 1 && +$upline['is_system_active']==1)
                {
                    $amount = $this->getPayoutAmount($upline['paid_as_rank_id'], $level);

                    if($amount > 0){
                        $this->insertPayout(
                            $upline['user_id'],
                            $account['customer_id'],
                            $amount,
                            100,
                            $amount,
                            // $upline['fname'] .' '. $upline['lname'] . ' has the rank of '. $upline['rank_name']. ' receives '.$amount."",
                            "Enrollment Level ".  $upline['level'],
                            $account['id'],
                            $level,
                            $account['sponsor_id']
                        );
                    }
                    $level++;
                }else {
                    $level++;
                }

                if($level > 5) break;


            }
        }
    }

    public function getAccounts($start = null, $length = 50)
    {

        $flowing = config('commission.energy-account-status-types.flowing');
        $flowing_pending_cancellation = config('commission.energy-account-status-types.flowing-pending-cancellation');

        $query = "
            select DISTINCT(l.customer_id), l.current_status, c.sponsor_id, c.id FROM 
              cm_energy_account_logs l 
              JOIN cm_energy_accounts c on l.customer_id = c.customer_id 
            WHERE 
              (l.current_status = $flowing OR l.current_status = $flowing_pending_cancellation)
              AND DATE(l.created_at) <= :end_date
              AND (l.notes = 'Record Updated' OR l.notes = 'Record Saved')
              ";

        $query = "SELECT cea.customer_id, cea.status AS current_status, cea.sponsor_id, cea.id
                      FROM cm_energy_accounts cea 
                    WHERE 
                      EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = cea.id AND FIND_IN_SET(l.current_status, '5,6') AND l.created_date <= :end_date)
                      AND NOT EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = cea.id AND FIND_IN_SET(l.current_status, '7') AND l.created_date <= :end_date2)";

        if($start !== null)
        {
            $query .= "LIMIT $start, $length";
        }

        $end_date = $this->getPeriodEndDate();

        $stmt = $this->db->prepare($query);
        $stmt->bindParam('end_date', $end_date);
        $stmt->bindParam('end_date2', $end_date);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public function getUserRank($user_id)
    {
        $cache_key = 'unilevel-residual-'.$user_id;

        if(Cache::has($cache_key))
            return Cache::pull($cache_key);

        $paid_as_rank = DailyRank::where('user_id', $user_id)
            ->where('rank_date', $this->getPeriodEndDate())
            ->first()
            ->pluck('paid_as_rank');
        Cache::put($cache_key, $paid_as_rank, 900);

        return $paid_as_rank;
    }

    private function getPayoutAmount($rank_id, $level)
    {
        $level -= 1; // make level zero-based

        $payout_amount =
            [
                config('commission.ranks.watt')             => [0.30, 0, 0, 0, 0],
                config('commission.ranks.watt-15')          => [0.35, 0, 0, 0, 0],
                config('commission.ranks.watt-30')          => [0.40, 0.15, 0, 0, 0],
                config('commission.ranks.watt-60')          => [0.45, 0.25, 0, 0, 0],
                config('commission.ranks.team-leader')      => [0.50, 0.35, 0.10, 0, 0],
                config('commission.ranks.group-leader')     => [0.55, 0.45, 0.10, 0, 0],
                config('commission.ranks.national-leader')  => [0.60, 0.55, 0.20, 0.10, 0],
                config('commission.ranks.global-leader')    => [0.65, 0.65, 0.30, 0.20, 0.10],
                config('commission.ranks.president-leader') => [0.70, 0.65, 0.40, 0.30, 0.20],
                config('commission.ranks.ceo-leader')       => [0.70, 0.65, 0.40, 0.30, 0.20],
                config('commission.ranks.founding-leader')  => [0.70, 0.65, 0.40, 0.30, 0.20]
            ];

        return $payout_amount[$rank_id][$level];
    }


    private function getUserUpline($user_id){
        $query = "
            WITH RECURSIVE cte AS (
                SELECT
                    id AS user_id,
                    sponsorid AS parent_id,
                    fname,
                    lname,
                    country,
                    created as dt_created,
                    cm.catid as catid,
                    levelid,
                    active,
                    0 AS `level`
                FROM users
                LEFT JOIN categorymap cm
                  ON users.id = cm.userid
                WHERE id = :user_id

                UNION ALL

                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    p.fname,
                    p.lname,
                    p.country,
                    p.created as dt_created,
                    cm.catid as catid,
                    p.levelid,
                    p.active,
                    cte.`level` + 1 `level`
                FROM users p
                LEFT JOIN categorymap cm
                  ON p.id = cm.userid
                INNER JOIN cte ON p.id  = cte.parent_id
                WHERE p.id != p.sponsorid
            )
            SELECT cte.*,
                ifnull(cdr.paid_as_rank_id, 0) as paid_as_rank_id,
                c.name as rank_name,
                cdr.is_active,
                cdr.is_system_active
             FROM cte 
            JOIN cm_daily_ranks cdr on cte.user_id = cdr.user_id
            JOIN cm_ranks c on c.id = cdr.paid_as_rank_id              
            WHERE cdr.rank_date = :rank_date AND NOT EXISTS(SELECT 1 FROM cm_terminated_associates ta WHERE ta.user_id = cte.user_id)
            AND NOT EXISTS(SELECT 1 FROM users u WHERE u.id = cte.user_id AND u.active = 'Canceled')";
        $rank_date = $this->getPeriodEndDate();
        $stmt = $this->db->prepare($query);
        $stmt->bindParam('user_id', $user_id);
        $stmt->bindParam('rank_date', $rank_date);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    private function generateTestAccounts(){
        return [
            ['customer_id'=>31641, 'id' => 1],
            ['customer_id'=>31632, 'id'=> 2]
        ];
    }

}