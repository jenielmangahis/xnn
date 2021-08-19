<?php

namespace Commissions\CommissionTypes;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use \PDO;
use DateTime;

use Commissions\Contracts\CommissionTypeInterface;


class BuilderBonus extends CommissionType implements CommissionTypeInterface
{
    protected $db;

    public function count()
    {
        return count($this->getQualifiedConsultants());
    }
    
    public function generateCommission($start, $length)
    {
        $consultants = $this->getQualifiedConsultants();
        foreach( $consultants as $c ){

            $this->log("Processing builder bonus for user id : " . $user_id);

            $sponsor_id = $c['sponsor_id'];
            $user_id    = $c['user_id'];

            $this->insertPayout(
                $sponsor_id,
                $user_id,
                100,
                0,
                0,
                " $sponsor_id has earned a builder bonus amounting of 100 ",
                $c['transaction_id'],
                0,
                $sponsor_id
            );
        }

    }

    private function getQualifiedConsultants()
    {

        $start_date = $this->getPeriodStartDate();
        $end_date   = $this->getPeriodEndDate();

        $sql = "
            SELECT 
                t.transaction_id,
                t.sponsor_id,
                t.user_id
            FROM v_cm_transactions t  
            JOIN users u ON t.sponsor_id = u.id
            JOIN cm_daily_volumes cdv ON t.sponsor_id = cdv.user_id
            JOIN cm_daily_ranks cdr ON cdr.volume_id = cdv.id 
            WHERE 
                t.purchaser_catid IN('8074,8077,8080,8083') AND 
                t.is_autoship = 1 
                AND 
                    t.transaction_date BETWEEN '$start_date' AND '$end_date'
                AND 
                EXISTS (
                    SELECT 1
                    FROM oc_autoship oa 
                    WHERE oa.trans_id = t.transaction_id
                )
                AND 
                (
                    SELECT SUM(dva.cv) 
                    FROM cm_daily_volumes dva 
                    WHERE dva.user_id = t.sponsor_id
                ) >= 50
                /*AND 
                (
                    SELECT SUM(ta.computed_rv) 
                    FROM v_cm_transactions ta 
                    WHERE ta.user_id = t.sponsor_id
                ) >= 400*/ 
                AND u.active = 'Yes'
                AND cdr.rank_id >= 1
                AND cdr.rank_date = '$end_date'
            GROUP BY t.sponsor_id
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    protected function setDates($end_date = null)
    {
        $end_date = $this->getRealCarbonDateParameter($end_date);

        $this->end_date = $end_date->format("Y-m-d");
        $this->start_date = $end_date->copy()->firstOfMonth()->format("Y-m-d");
    }

    public function run($comissions_period_id)
    {
        $this->setDates($end_date);

        $this->process();
    }

    public function getEndDate()
    {
        if (!isset($this->end_date)) {
            throw new Exception("End date is not set.");
        }

        return $this->end_date;
    }

    public function setEndDate($end_date)
    {
        $this->throwIfInvalidDateFormat($end_date);
        $this->end_date = $end_date;
    }

    public function getStartDate()
    {
        if (!isset($this->start_date)) {
            throw new Exception("Start date is not set.");
        }
        return $this->start_date;
    }

    public function setStartDate($start_date)
    {
        $this->throwIfInvalidDateFormat($start_date);
        $this->start_date = $start_date;
    }
}