<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 11/23/2020
 * Time: 7:25 PM
 * Task Link: https://3.basecamp.com/3526928/buckets/10144002/todos/3210686149
 */

namespace Commissions\CommissionTypes;

use App\DailyRank;
use App\User;
use Commissions\Contracts\CommissionTypeInterface;

class ImmediateEarnings  extends CommissionType implements CommissionTypeInterface
{
    const FIRST_THREE_PEA_BONUS = 20;
    const FORTH_PEA_BONUS = 5;
    const WATT_60_ABOVE_BONUS = 3;
    const NATIONAL_LEADER_ABOVE_BONUS = 2;

    public function count()
    {
        return count($this->getAccounts());
    }

    public function generateCommission($start, $length)
    {
        $watt_rank = config('commission.ranks.watt');
        $this->log('Getting accounts... ');

        $accounts = $this->getAccounts($start, $length);
        $current_associate = 0;
        $number_of_accounts = 0;
        foreach($accounts as $account)
        {


            $this->log('Evaluating account '. $account['id']);

            /**
             * Part 1
             *
             * */
            if($account['enrollment_order_count'] >= 4 
                && $account['is_active'] == 1 
                && $account['is_system_active'] == 1 
                && $account['paid_as_rank_id'] >= $watt_rank 
                && $account['active'] != 'Canceled'){

                $this->associateBonus($account['sponsor_id'], $account['customer_id'], $account['account_type'], $account['id']);

            } else{

                $this->log("Associate {$account['sponsor_id']} is not qualified for part 1.");
            }

            $level_1_sponsor = $this->getLevel1Sponsor($account['sponsor_id']);

            $level_1_sponsor_id = 0;

            if($level_1_sponsor)
            {
                $level_1_sponsor_id = $level_1_sponsor['id'];

                $level_1_sponsor_rank = $this->getLevel1SponsorRankID($level_1_sponsor_id);
                /** Part 2 **/
                if($this->level1SponsorQualified($account['enrollment_order_count'], $level_1_sponsor_rank) && $level_1_sponsor['is_active'] == 1 && $level_1_sponsor['is_system_active'] == 1)
                {
                    $this->level1UplineBonus($level_1_sponsor_id, $account['sponsor_id'], $account['customer_id'],  $account['id']);
                }
                else
                {
                    $this->log("(Part 2) Level 1 sponsor $level_1_sponsor_id is not qualified. ");
                }


                /**
                 * Part 3
                 */

                if($account['enrollment_order_count'] >= 4 && $level_1_sponsor_rank >= $watt_rank && $level_1_sponsor['is_active'] == 1 && $level_1_sponsor['is_system_active'] == 1)
                {
                    $this->forthPEAOnwardsBonus($level_1_sponsor_id, $account['sponsor_id'], $account['customer_id'], $account['id']);
                }
                else
                {
                    $this->log("(Part 3) Level 1 sponsor $level_1_sponsor_id is not qualified.");
                }


            }
            else
            {
                $this->log("(Part 2) Level 1 sponsor of {$account['sponsor_id']} is not qualified");
            }


            /**
             * Part 4 & Part 5
             *
             */


            $watt_60_rank = config('commission.ranks.watt-60');
            $national_leader = config('commission.ranks.national-leader');

            $uplines = $this->getUserUpline($level_1_sponsor_id, 2);

            $part_4_is_found = false;
            $part_4_level = 0;

            $part_5_is_found = false;
            $part_5_level = 0;
            foreach($uplines as $upline){
                if($upline['paid_as_rank_id'] >= $watt_60_rank && $part_4_is_found === false)
                {
                    $part_4_level = $upline['level'];
                    $part_4_is_found = true;

                    $this->watt60AboveBonus($upline['user_id'], $account['sponsor_id'], $account['customer_id'], $upline['paid_as_rank_id'], $part_4_level, $account['id']);

                }

                else if($upline['paid_as_rank_id'] >= $national_leader && $part_4_is_found === true)
                {
                    $part_5_level = $upline['level'];
                    $part_5_is_found = true;
                    $this->nationalLeaderAboveBonus($upline['user_id'], $account['sponsor_id'], $account['customer_id'], $upline['paid_as_rank_id'], $part_5_level, $account['id']);
                }

                if($part_4_is_found && $part_5_is_found) break;

            }

            if($part_4_is_found === false) $this->log('No uplines found with watt 60 and higher paid-as-rank');
            if($part_5_is_found === false) $this->log('No uplines found with national leader and higher paid-as-rank');
        }
    }

    private function associateBonus($sponsor_id, $customer_id, $energy_account_type, $energy_account_id)
    {
        $residential_energy_type = config('commission.energy-account-types.residential');
        $bonus_amount = 20;

        if($residential_energy_type <> $energy_account_type) $bonus_amount = 25;

        $this->insertPayout(
            $sponsor_id,
            $customer_id,
            $bonus_amount,
            100,
            $bonus_amount,
            'Part 1 :  As a Watt or above title, newly enrolled approved energy account 20 or 25 euro.',
            $energy_account_id,
            0,
            $sponsor_id
        );
    }

    private function level1UplineBonus($payee_id, $sponsor_id, $customer_id, $energy_account_id)
    {
        $bonus_amount = self::FIRST_THREE_PEA_BONUS;

        $this->insertPayout(
            $payee_id,
            $customer_id,
            $bonus_amount,
            100,
            $bonus_amount,
            'Part 2 (for sponsor) : Level 1-direct sponsor of a Spark will earn 20 euro for each 3 first approved Energy Accounts.',
            $energy_account_id,
            1,
            $sponsor_id
        );
    }

    private function getLevel1Sponsor($user_id)
    {
        $sql = "
            WITH RECURSIVE upline (user_id, parent_id, `level`) AS (
                SELECT
                    id AS user_id,
                    sponsorid AS parent_id,
                    1 AS `level`
                FROM users
                WHERE id = $user_id
                
                UNION ALL
                
                SELECT
                    u.id AS user_id,
                  u.sponsorid AS parent_id,
                  upline.`level` + 1 `level`
                FROM users u
                INNER JOIN upline ON upline.parent_id = u.id
            )
            SELECT u.user_id FROM upline u
            JOIN users s ON s.id = u.user_id
            WHERE s.active <> 'Canceled'
            AND u.user_id <> $user_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $user = $stmt->fetch();

        if($user){
            $rank = DailyRank::ofMember($user['user_id'])
            ->date($this->getPeriodEndDate())
            ->first();

            if ($rank) {

                return ['id' => $user['user_id'], 'is_active' => $rank['is_active'], 'is_system_active' => $rank['is_system_active']];
            }
        }

        return false;
    }

    public function getAccounts($start = null, $length = null)
    {

        $approved = config('commission.energy-account-status-types.approved-pending-flowing');
        $immediate_earnings = config('commission.commission-types.weekly-immediate-earnings');
        $query = "SELECT
                    cea.*, 
                    r.is_system_active, 
                    r.is_active, 
                    r.is_canceled, 
                    r.paid_as_rank_id,
                    u.active,
                    (1 + (SELECT 
                      COUNT(DISTINCT cea2.id) 
                      FROM cm_energy_accounts cea2 
                      JOIN cm_energy_account_logs app ON app.energy_account_id = cea2.id AND app.current_status = 4
                      LEFT JOIN cm_energy_account_logs can ON can.energy_account_id = cea2.id 
                        AND can.current_status = 7 AND can.created_date <= DATE(app.created_date + INTERVAL 6 - WEEKDAY(app.created_date) DAY)
                      WHERE can.energy_account_id IS NULL AND cea2.sponsor_id = cea.sponsor_id
                      AND (app.created_at < l.created_at OR (app.id < l.id AND app.created_at = l.created_at))
                      )
                    ) AS enrollment_order_count
                    FROM cm_energy_accounts cea 
                    JOIN cm_energy_account_logs l ON l.energy_account_id = cea.id AND l.current_status = 4
                    LEFT JOIN cm_energy_account_logs ll ON ll.energy_account_id = cea.id AND ll.current_status = 7 AND ll.created_date <= :end_date1
                    LEFT JOIN cm_daily_ranks r ON r.user_id = cea.sponsor_id AND r.rank_date = :end_date2
                    JOIN users u ON u.id = cea.sponsor_id
                    WHERE l.created_date BETWEEN :start_date AND :end_date3
                    AND 
                    (
                        NOT EXISTS(
                            SELECT 1
                              FROM cm_commission_payouts ccp 
                              JOIN cm_energy_accounts cea2 ON cea2.id = ccp.transaction_id
                              JOIN cm_commission_periods ccp1 ON ccp1.id = ccp.commission_period_id
                             WHERE ccp1.is_locked = 1 AND ccp1.commission_type_id = $immediate_earnings AND cea2.reference_id = cea.reference_id
                         )
                        AND NOT EXISTS(
                            SELECT 1 FROM migrated_energy_accounts_paid WHERE account_num = cea.reference_id
                            )
                         OR 
                         EXISTS (SELECT 1 FROM cm_energy_account_logs lc WHERE lc.energy_account_id = cea.id AND lc.current_status = 7
                          AND lc.created_date <= DATE_SUB(:end_date4, INTERVAL 6 MONTH)) -- canceled accounts more than 6 months ago
                    )
                    AND ll.id IS NULL";

        if($start !== null)
        {
            $query .= " LIMIT $start, $length";
        }

        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

        $stmt = $this->db->prepare($query);
        $stmt->bindParam('start_date'   ,$start_date);
        $stmt->bindParam('end_date1'     ,$end_date);
        $stmt->bindParam('end_date2'     ,$end_date);
        $stmt->bindParam('end_date3'     ,$end_date);
        $stmt->bindParam('end_date4'     ,$end_date);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    private function getLevel1SponsorRankID($sponsor_id)
    {
        $end_date = $this->getPeriodEndDate();
        $rank = DailyRank::ofMember($sponsor_id)
            ->date($end_date)
            ->first();

        return $rank['paid_as_rank_id'];
    }

    private function level1SponsorQualified($level_1_bonus_count, $rank_id)
    {
        $watt_rank = config('commission.ranks.watt');
        if($level_1_bonus_count <= 3  && $rank_id >= $watt_rank) return true;

        return false;
    }

    private function forthPEAOnwardsBonus($payee_id, $sponsor_id, $customer_id, $energy_account_id)
    {

        $bonus_amount = self::FORTH_PEA_BONUS;

        $this->insertPayout(
            $payee_id,
            $customer_id,
            $bonus_amount,
            100,
            $bonus_amount,
            'Part 3:  5 Euro earned from an energy account enrolled by an associate Level 1 - starting from the 4th inserted.',
            $energy_account_id,
            1,
            $sponsor_id
        );
    }

    private function getUserUpline($user_id, $level){
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
                    1 AS `level`
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
                IFNULL(cdr.paid_as_rank_id, 0) as paid_as_rank_id,
                c.name as rank_name
             FROM cte JOIN 
                cm_daily_ranks cdr on cte.user_id = cdr.user_id
            JOIN cm_ranks c on c.id = cdr.paid_as_rank_id              
            WHERE cte.level >= :level
            AND cdr.rank_date = :rank_date
            AND cdr.is_active = 1 
            AND cdr.is_system_active = 1
            AND cte.active = 'Yes'
            ";
        $rank_date = $this->getPeriodEndDate();
        $stmt = $this->db->prepare($query);
        $stmt->bindParam('user_id', $user_id);
        $stmt->bindParam('level', $level);
        $stmt->bindParam('rank_date', $rank_date);
        $stmt->execute();

        return  $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    private function watt60AboveBonus($payee_id, $sponsor_id, $customer_id, $rank_id, $level, $energy_account_id)
    {

        $bonus_amount = self::WATT_60_ABOVE_BONUS;

        $this->insertPayout(
            $payee_id,
            $customer_id,
            $bonus_amount,
            100,
            $bonus_amount,
            'Part 4: Watt 60 or above earns 3 euro for each approved energy account, down to next Watt 60.',
            $energy_account_id,
            $level,
            $sponsor_id
        );
    }


    private function nationalLeaderAboveBonus($payee_id, $sponsor_id, $customer_id, $rank_id, $level, $energy_account_id)
    {

        $bonus_amount = self::NATIONAL_LEADER_ABOVE_BONUS;

        $this->insertPayout(
            $payee_id,
            $customer_id,
            $bonus_amount,
            100,
            $bonus_amount,
            'Part 5: National Leader or above earns 2 euro for each approved energy account, down to next National leader.',
            $energy_account_id,
            $level,
            $sponsor_id
        );
    }

    private function countAssociateApprovedAccounts($sponsor_id, $energy_account_id)
    {
        $approved = config('commission.energy-account-status-types.approved-pending-flowing');

        $query = "
            SELECT
                    count(1)
                  FROM cm_energy_account_logs l
                  JOIN cm_engery_account cea on l.energy_account_id = cea.id 
                  WHERE l.energy_account_id = acc.id
                  AND FIND_IN_SET(l.current_status, '$approved')
                  AND  cea.sponsor_id = :sponsor_id 
                  AND cea.id < :energy_account_id
        ";


        $stmt = $this->db->prepare($query);

        $stmt->bindParam('sponsor_id', $sponsor_id);
        $stmt->bindParam('energy_account_id', $energy_account_id);
        $stmt->execute();

        return $stmt->fetchColumn();


    }
}