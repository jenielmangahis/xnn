<?php


namespace Commissions\Contracts\Repositories;


interface PayoutRepositoryInterface
{
    /**
     * @param int $period_id The Commission Period ID
     * @param int $payee_id The member who receives the commission
     * @param int $user_id The downline or the member itself
     * @param float $commission_value The commission value of the transaction or the amount of the bonus
     * @param float $percent The percentage of the commission that the member receives
     * @param float $amount The payout amount that the member receives
     * @param string|null $remarks The remarks
     * @param int $transaction_id The transaction id
     * @param int $level The level of the downline/upline that member receive commission from
     * @param int $sponsor_id
     */
    public function insertPayout($period_id, $payee_id, $user_id, $commission_value, $percent, $amount, $remarks = '',  $transaction_id = 0, $level = 0, $sponsor_id = 0);

    /**
     * @param int $period_id  The Commission Period ID
     * @return array
     */
    public function getSummary($period_id);

    /**
     * @param int $period_id  The Commission Period ID
     * @return array
     */
    public function getDetails($period_id);
}