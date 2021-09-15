<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 8/6/2019
 * Time: 8:42 AM
 */

namespace Commissions\CommissionTypes;

use App\CommissionPeriod;
use Commissions\Contracts\BackgroundWorkerLoggerInterface;
use Commissions\Contracts\CommissionTypeInterface;
use Commissions\Contracts\Repositories\PayoutRepositoryInterface;
use Illuminate\Support\Facades\DB;

abstract class CommissionType
{
    protected $commission_period;
    protected $logger;
    protected $payout_repository;
    protected $db;

    public function __construct(CommissionPeriod $commission_period, BackgroundWorkerLoggerInterface $logger, PayoutRepositoryInterface $payout_repository)
    {
        $this->commission_period = $commission_period;
        $this->logger = $logger;
        $this->payout_repository = $payout_repository;
        $this->db = DB::connection()->getPdo();
    }

    public function log($message = "          ")
    {
        $this->logger->log($message);
    }
    public function getPeriodStartDate()
    {
        return $this->commission_period->start_date;
    }
    public function getPeriodEndDate()
    {
        if($this->commission_period->end_date > date("Y-m-d")) {
            return date("Y-m-d");
        }

        return $this->commission_period->end_date;
    }
    public function getPeriodId()
    {
        return $this->commission_period->id;
    }

    public function getCommissionType()
    {
        return $this->commission_period->type->name;
    }

    public function isSingleProcess()
    {
        return false;
    }

    public function beforeCommissionRun()
    {
        //
    }

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
    public function insertPayout($payee_id, $user_id, $commission_value, $percent, $amount, $remarks = '', $transaction_id = 0, $level = 0, $sponsor_id = 0)
    {
        $this->payout_repository->insertPayout($this->getPeriodId(), $payee_id, $user_id, $commission_value, $percent, $amount, $remarks, $transaction_id, $level, $sponsor_id);
    }

    /**
     *
     * @return array
     */
    public function getSummary()
    {
        return $this->payout_repository->getSummary($this->getPeriodId());
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->payout_repository->getDetails($this->getPeriodId());
    }


    /*
     *
     * */
    public function afterCommissionRun(){

        return $this->payout_repository->saveAdditionalInfo($this->getPeriodId());
    }


}