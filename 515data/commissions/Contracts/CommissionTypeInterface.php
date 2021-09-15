<?php


namespace Commissions\Contracts;

interface CommissionTypeInterface
{
    public function count();
    public function generateCommission($start, $length); // runCommission
    public function isSingleProcess();
    public function beforeCommissionRun();
    public function log($message = "          ");
    public function getPeriodStartDate();
    public function getPeriodEndDate();
    public function getPeriodId();
    public function getCommissionType();
    /**
     * @param int $sponsor_id  The member who receives the commission
     * @param int $user_id  The downline or the member itself
     * @param float $commission_value  The commission value of the transaction or the amount of the bonus
     * @param float $percent  The percentage of the commission that the member receives
     * @param float $amount  The payout amount that the member receives
     * @param string|null $remarks The remarks
     * @param int $transaction_id The transaction id
     * @param int $level The level of the downline/upline that member receive commission from
     */
    public function insertPayout($sponsor_id, $user_id, $commission_value, $percent, $amount, $remarks = '',  $transaction_id = 0, $level = 0);

    /**
     * @return array
     */
    public function getSummary();

    /**
     * @return array
     */
    public function getDetails();

    // public function applyAdjustments(); // create adjustment class
    // public function applyClawbacks(); // create clawback class
    // public function generateReportLinks($prefix = ''); // create report class
}