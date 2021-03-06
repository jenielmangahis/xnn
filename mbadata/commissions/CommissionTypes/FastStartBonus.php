<?php

namespace Commissions\CommissionTypes;


class FastStartBonus extends CommissionType
{
    const MAX_LEVEL = 10;

    public function isSingleProcess()
    {
        return true;
    }
    public function count()
    {
        return count($this->getOrders());
    }

    public function generateCommission($start, $length)
    {
        $orders = $this->getOrders($start, $length);

        foreach($orders as $key => $order)
        {
            $this->log("Processing Order ID " . $order['transaction_id']);
            $transaction_id = $order['transaction_id'];
            $user_id = $order['user_id'];
            $sponsor_id = $order['sponsor_id'];
            $cv = $order['cv'];

            $payingLevel = 0;
            $uplines = $this->getUserUpline($sponsor_id);
            
            if(count($uplines) > 0) {
                foreach($uplines as $keys => $upline)
                {
                    $payingLevel++;
                    $upline_user_id = $upline['user_id'];
                    $upline_rank_id = $upline['rank_id'];
                    if(+$upline['is_customer'] || !+$upline['is_active']) continue; //if upline is customer or inactive no commission will be paid out

                    $min_ibo = config('commission.ranks.ibo');

                    if($upline_rank_id >= $min_ibo) {
                        
                        $percentage = $this->getPercentage($upline_rank_id,$payingLevel);
                        $this->log("User ID: $upline_user_id | Upline paid as rank id: $upline_rank_id | Level: $payingLevel");
                        $amount = +$cv * +$percentage;

                        if($amount > 0) {
                            $this->insertPayout(
                                $upline_user_id,
                                $user_id,
                                $cv,
                                $percentage * 100,
                                $amount,
                                "Member $upline_user_id Rank Id $upline_rank_id",
                                $transaction_id,
                                $payingLevel,
                                $sponsor_id
                            );
                        }
                        if($payingLevel > self::MAX_LEVEL) break;
                    }
                }
            }

            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    public function getOrders($start = null, $length = null)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date = $this->getPeriodEndDate();

		/*
        $sql = "
            SELECT 
                t.transaction_id,
                t.user_id,
                t.sponsor_id,
                t.computed_cv AS cv
            FROM v_cm_transactions t 
            JOIN (
                SELECT MIN(tt.transaction_id) AS id 
                FROM v_cm_transactions AS tt
                JOIN transaction_products tp ON tp.transaction_id = tt.transaction_id
                WHERE DATE(tt.transaction_date) BETWEEN '$start_date' AND '$end_date'
                GROUP BY tt.transaction_id
            ) AS first_transaction ON first_transaction.id = t.transaction_id
        ";
		*/

		$sql = "
			SELECT 
				t.transaction_id,
				t.user_id,
				t.sponsor_id,
				t.computed_cv AS cv
			FROM v_cm_transactions t 
			WHERE DATE(t.transaction_date) BETWEEN '$start_date' AND '$end_date'
			ORDER BY t.transaction_id
		";

        if ($start !== null) {
            $sql .= " LIMIT {$start}, {$length}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPercentage($rank_id,$level)
    {
        $level = $level - 1; // make level zero-based

        $percentage =
            [
                config('commission.ranks.ibo')                        => [0.50, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                config('commission.ranks.apprentice-trader')          => [0.50, 0.10, 0.05, 0, 0, 0, 0, 0, 0, 0],
                config('commission.ranks.junior-trader')              => [0.50, 0.10, 0.05, 0.04, 0, 0, 0, 0, 0, 0],
                config('commission.ranks.novice-trader')              => [0.50, 0.10, 0.05, 0.04, 0.03, 0, 0, 0, 0, 0],
                config('commission.ranks.qualified-trader')           => [0.50, 0.10, 0.05, 0.04, 0.03, 0.02, 0, 0, 0, 0],
                config('commission.ranks.team-trader')                => [0.50, 0.10, 0.05, 0.04, 0.03, 0.02, 0.02, 0, 0, 0],
                config('commission.ranks.national-trader')            => [0.50, 0.10, 0.05, 0.04, 0.03, 0.02, 0.02, 0.02, 0, 0],
                config('commission.ranks.international-trader')       => [0.50, 0.10, 0.05, 0.04, 0.03, 0.02, 0.02, 0.02, 0, 0],
                config('commission.ranks.world-trader')               => [0.50, 0.10, 0.05, 0.04, 0.03, 0.02, 0.02, 0.02, 0.01, 0],
                config('commission.ranks.global-trader')              => [0.50, 0.10, 0.05, 0.04, 0.03, 0.02, 0.02, 0.02, 0.01, 0.01]
            ];
        
        return $percentage[+$rank_id][+$level];
    }

    private function getUserUpline($user_id) {
        $customer = config('commission.member-types.customers');
        $query = "
            WITH RECURSIVE cte AS (
                SELECT
                    id AS user_id,
                    sponsorid AS parent_id,
                    fname,
                    lname,
                    levelid,
                    active,
                    0 AS `level`
                FROM users
                WHERE id = :user_id

                UNION ALL

                SELECT
                    p.id AS user_id,
                    p.sponsorid AS parent_id,
                    p.fname,
                    p.lname,
                    p.levelid,
                    p.active,
                    cte.`level` + 1 `level`
                FROM users p
                INNER JOIN cte ON p.id  = cte.parent_id
                WHERE p.id != p.sponsorid
            )
            SELECT 
                cte.*,
                IFNULL(cdr.paid_as_rank_id, 0) as paid_as_rank_id,
                IFNULL(cdr.rank_id, 0) AS rank_id,
                cdr.is_active,
                cdr.is_system_active,
                IF(FIND_IN_SET(cdr.cat_id,'$customer'), 1, 0) is_customer
            FROM cte 
            JOIN cm_daily_ranks cdr on cte.user_id = cdr.user_id
            JOIN cm_ranks c on c.id = cdr.paid_as_rank_id              
            WHERE cdr.rank_date = :rank_date";

        $end_date = $this->getPeriodEndDate();
        $stmt = $this->db->prepare($query);
        $stmt->bindParam('user_id', $user_id);
        $stmt->bindParam('rank_date', $end_date);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
}