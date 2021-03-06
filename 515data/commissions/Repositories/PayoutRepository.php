<?php


namespace Commissions\Repositories;


use App\CommissionPayout;
use Commissions\Contracts\Repositories\PayoutRepositoryInterface;
use Illuminate\Support\Facades\DB;
use \PDO;

class PayoutRepository implements PayoutRepositoryInterface
{
    protected $db;

    /**
     * @inheritDoc
     */
    public function insertPayout($period_id, $payee_id, $user_id, $commission_value, $percent, $amount, $remarks = '', $transaction_id = 0, $level = 0, $sponsor_id)
    {
        $payout = new CommissionPayout();
        $payout->commission_period_id = $period_id;
        $payout->transaction_id = $transaction_id;
        $payout->user_id = $user_id;
        $payout->payee_id = $payee_id;
        $payout->level = $level;
        $payout->commission_value = $commission_value;
        $payout->percent = $percent;
        $payout->amount = $amount;
        $payout->remarks = $remarks;
        $payout->sponsor_id = $sponsor_id;

        $payout->save();
    }

    /**
     * @param int $period_id  The Commission Period ID
     * @return array
     */
    public function getSummary($period_id)
    {
        $summary = DB::table("cm_commission_payouts As cp")
            ->selectRaw("
                s.id As user_id,
                CONCAT(s.fname, ' ') As first_name,
                CONCAT(s.lname, ' ') As last_name,
                CONCAT(s.business, ' ') As business_name,
                CONCAT(s.site, ' ') As username,
                ROUND(SUM(cp.amount), 2) As total_payout
            ")
            ->leftJoin('users AS s', 's.id', '=', 'cp.payee_id')
            ->groupBy('s.id')
            ->where('cp.commission_period_id', $period_id)
            ->get();

        $sponsor_gift_cards_summary = DB::table("cm_gift_cards AS cp")
            ->selectRaw("
                s.id AS user_id,
                CONCAT(s.fname, ' ') As first_name,
                CONCAT(s.lname, ' ') As last_name,
                'N/A' AS business_name,
                s.site AS username,
                cp.amount AS total_payout
            ")
            ->leftJoin('users AS s', 's.id', '=', 'cp.sponsor_id')
            ->where('cp.commission_period_id', $period_id)
            ->get();

        $finalResult = $summary->merge($sponsor_gift_cards_summary);
        return $finalResult;
    }

    /**
     * @param int $period_id  The Commission Period ID
     * @return array
     */
    public function getDetails($period_id)
    {
        $details = DB::table('cm_commission_payouts As p')
            ->leftJoin('customers AS u', 'u.id', '=', 'p.user_id')
            ->leftJoin('users AS s', 's.id', '=', 'p.payee_id')
            ->leftJoin('cm_commission_periods As pr', 'pr.id', '=', 'p.commission_period_id')
            ->leftJoin('cm_commission_types As t', 't.id', '=', 'pr.commission_type_id')
            ->leftJoin('cm_energy_accounts As cea', 'p.transaction_id', '=', 'cea.id')
            ->leftJoin('cm_energy_account_types As ceat', 'cea.account_type', '=', 'ceat.id')
            ->leftJoin(DB::raw(
                "(SELECT SUM(amount) AS total_payout, payee_id  from cm_commission_payouts WHERE commission_period_id = $period_id
                GROUP BY payee_id) as tp  
                "
            ), 'tp.payee_id', '=', 'p.payee_id')

            ->select( 'p.payee_id As associate_id',
                DB::raw("CONCAT(s.fname, ' ') As associate_fname"),
                DB::raw("CONCAT(s.lname, ' ') As associate_lname"),
                'p.payees_paid_as_title As paid_as_title',
                'u.memberid as customer_id',
                DB::raw("CONCAT(u.fname, ' ') As cust_fname"),
                DB::raw("CONCAT(LEFT(u.lname, 1), ' ') As cust_lname"),
				DB::raw("IF(COALESCE(u.fname,'') = '', CONCAT(u.business, ' '), '') As cust_company_name"),
                'p.sponsor_id As asso_id_cust_enroller ',
                'cea.reference_id As `pod/pdr`',
                'ceat.type As type_of_account',
                'p.amount As payout',
                'p.level As level',
                'p.energy_account_status',
                'tp.total_payout',
                "p.payees_pea_approved",
                "p.payees_pea_flowing",
                'p.remarks')
            ->where('p.commission_period_id', $period_id)
            ->orderByRaw('p.payee_id, p.level ASC')
            ->get();

        $sponsor_gift_cards = DB::table('cm_gift_cards As p')
            ->leftJoin('users AS u', 'u.id', '=', 'p.user_id')
            ->leftJoin('users AS s', 's.id', '=', 'u.sponsorid')
            ->leftJoin('cm_commission_periods As pr', 'pr.id', '=', 'p.commission_period_id')
            ->leftJoin('cm_commission_types As t', 't.id', '=', 'pr.commission_type_id')
            ->selectRaw(
                "p.sponsor_id AS payee_id,
                s.fname AS associate_fname,
                s.lname AS associate_lname,
                u.id AS user_id,
                p.user_id as cust_id,
                CONCAT(u.fname, ' ') As cust_fname,
                CONCAT(u.lname, ' ') As cust_lname,
				u.business as cust_company_name,
                p.sponsor_id AS asso_id ,
                'N\A' As `pod/pdr`,
                'N\A' As `type_of_account`,
                p.amount AS payout,
                '' As level,
                '' as energy_account_status,
                '' as total_payout,
                '' as payees_pea_approved,
                '' as payees_pea_flowing,
                CONCAT('ID ',u.id, ' Join Date: ',DATE(u.`created`),' | Payout Type: ', 'Gift Card') AS remarks"
            )
            ->where('p.commission_period_id', $period_id)
            ->get();
        $finalResult = $details->merge($sponsor_gift_cards);
        return $finalResult;
    }

    /*
     *
     * */

    public function saveAdditionalInfo($period_id)
    {
        /* update eneergy_account_status */
        $this->db = DB::connection()->getPdo();


        DB::transaction(function () use ($period_id){
            $approved = config('commission.energy-account-status-types.approved-pending-flowing');
            $flowing = config('commission.energy-account-status-types.flowing');
            $cancelation = config('commission.energy-account-status-types.flowing-pending-cancellation');
            $cancelled = config('commission.energy-account-status-types.cancelled');

            $query = "
            UPDATE cm_commission_payouts p 
            JOIN (
                SELECT l.energy_account_id, t.display_text, created_date, 
                rank() over(PARTITION BY energy_account_id ORDER BY l.current_status DESC) AS rid 
                 FROM cm_energy_account_logs l 
                 JOIN cm_energy_account_status_types t ON t.id = l.current_status 
                 WHERE created_date <= (SELECT end_date FROM cm_commission_periods WHERE id = :period_id)
             ) AS rs 
             ON p.`transaction_id` = rs.energy_account_id
             SET p.`energy_account_status` = rs.display_text 
                WHERE p.`commission_period_id` = :period_id1 
             AND rs.rid = 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam("period_id", $period_id);
            $stmt->bindParam("period_id1", $period_id);
            $stmt->execute();


            $query = "
            UPDATE cm_commission_payouts p 
            JOIN (
                  SELECT
                      COUNT(acc.id) as approved_count,
                      acc.sponsor_id
                    FROM
                      cm_energy_accounts acc
                      JOIN cm_energy_account_logs l
                        ON acc.id = l.`energy_account_id`
                    WHERE FIND_IN_SET(l.`current_status`, '".$approved."')
                      AND EXISTS
                      (SELECT
                        1
                      FROM
                        cm_commission_periods p
                      WHERE l.created_date BETWEEN p.start_date
                        AND p.end_date
                        AND p.id = :period_id)
                    GROUP BY acc.`sponsor_id`) as a  
                    ON p.payee_id = a.sponsor_id
            SET p.payees_pea_approved = a.approved_count
            WHERE p.commission_period_id = :period_id1 
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam("period_id", $period_id);
            $stmt->bindParam("period_id1", $period_id);
            $stmt->execute();

			/*
            $all_approved_status = implode(',', [$flowing, $cancelation]);
            $query = "
            UPDATE cm_commission_payouts p 
            JOIN (
                  SELECT COUNT(acc.id) as flowing_count,
                    acc.sponsor_id 
                      FROM cm_energy_accounts acc
                    WHERE EXISTS 
                        (SELECT
                          1
                        FROM cm_energy_account_logs l
                        WHERE l.energy_account_id = acc.id
                        AND FIND_IN_SET(l.current_status, '".$all_approved_status."')
                        AND l.created_date <= (SELECT end_date FROM cm_commission_periods WHERE id = :period_id))
                    AND NOT EXISTS
                        (SELECT
                          1
                        FROM cm_energy_account_logs l
                        WHERE l.energy_account_id = acc.id
                        AND FIND_IN_SET(l.current_status, '".$cancelled."')
                        AND l.created_date <= (SELECT end_date FROM cm_commission_periods WHERE id = :period_id1))
                    GROUP BY acc.sponsor_id) AS flowing 
                    
                    ON p.payee_id = flowing.sponsor_id
                    set p.payees_pea_flowing  = flowing.flowing_count 
                    WHERE p.commission_period_id = :period_id2 
            ";
			*/

			$query = "
				UPDATE cm_commission_payouts p 
				JOIN (
					SELECT
						pea_flowing AS flowing_count,
						user_id AS sponsor_id
					FROM cm_daily_volumes
					WHERE volume_date = (SELECT end_date FROM cm_commission_periods WHERE id = :period_id)
				) AS flowing
				ON p.payee_id = flowing.sponsor_id
				set p.payees_pea_flowing  = flowing.flowing_count 
				WHERE p.commission_period_id = :period_id1 
			";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam("period_id", $period_id);
            $stmt->bindParam("period_id1", $period_id);
            $stmt->execute();

            /* Paid as rank name
             *
             * */

            $query = "
            UPDATE cm_commission_payouts p 
            JOIN (
              SELECT user_id, r.`name` FROM cm_daily_ranks cdr 
              JOIN cm_ranks r ON cdr.`paid_as_rank_id` = r.id 
              WHERE rank_date =(SELECT end_date FROM cm_commission_periods WHERE id = :period_id))
               AS paid_rank 
                    
                    ON p.payee_id = paid_rank.user_id
                    set p.payees_paid_as_title   = paid_rank.name 
                    WHERE p.commission_period_id = :period_id1 
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam("period_id", $period_id);
            $stmt->bindParam("period_id1", $period_id);
            $stmt->execute();

        });

    }
}