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

		foreach ($users as $user) {
            $user_id = $user['user_id'];
            $sponsor_id = $user['sponsor_id'];

			$payoutRank = 9;

			while ($payoutRank > 3) {

				if ($user['maintained_r'.$payoutRank] == 'Yes') {
					$this->log("User ID: $user_id maintained rank $payoutRank for 90 days");

					if ($this->hasReceivedBonus($user_id, $payoutRank)) {
						$this->log("User ID: $user_id already received the bonus for $payoutRank rank");

					} else {
						$this->log("User ID: $user_id is receiving the bonus for $payoutRank rank");

						$amount = $this->getBonus($payoutRank);

						$this->insertPayout(
							$user_id,
							$user_id,
							0,
							100,
							$amount,
							"Rank ID: $payoutRank received Rank Consistency Bonus",
							0,
							0,
							$sponsor_id
						);
		
						$this->addReceivedBonus(
							[
								'user_id' => $user_id,
								'commission_period_id' => $this->getPeriodId(),
								'rank_id' => $payoutRank,
								'is_received' => 1
							]);
	
						break;
					}
				}

				$payoutRank--;
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
					IF(SUM(IF(cdr.rank_id >= 4, 1, 0))=3,'Yes','No') AS maintained_r4, 
					IF(SUM(IF(cdr.rank_id >= 5, 1, 0))=3,'Yes','No') AS maintained_r5, 
					IF(SUM(IF(cdr.rank_id >= 6, 1, 0))=3,'Yes','No') AS maintained_r6, 
					IF(SUM(IF(cdr.rank_id >= 7, 1, 0))=3,'Yes','No') AS maintained_r7, 
					IF(SUM(IF(cdr.rank_id >= 8, 1, 0))=3,'Yes','No') AS maintained_r8, 
					IF(SUM(IF(cdr.rank_id >= 9, 1, 0))=3,'Yes','No') AS maintained_r9
				FROM cm_daily_ranks cdr 
                JOIN users u ON u.id = cdr.user_id
				WHERE cdr.`rank_id` >= 4 -- minimum rank
                AND cdr.`rank_date` BETWEEN DATE_SUB('$end_date', INTERVAL 90 DAY) AND '$end_date'
				AND cdr.`rank_date` = LAST_DAY(cdr.`rank_date`)
				GROUP BY cdr.user_id";

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

    public function hasReceivedBonus($user_id, $rank_id)
    {
        $sql = "SELECT
                  COUNT(user_id) c
                FROM cm_rank_consistency 
                WHERE user_id = $user_id AND rank_id = $rank_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return +$stmt->fetchColumn() > 0;
    }

}