<?php

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;
use Carbon\Carbon;
use \PDO;
use DateTime;


class MonthlyCustomerProfit extends CommissionType
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
            $transactions = $this->getTransactions($c['sponsorid']);
            
            if(count($transactions) <= 0){
                $this->log('No transactions found for sponsor id ' . $c['sponsorid']);
                continue;
            }

            foreach( $transactions as $t ){
                $payee_id = $t['sponsor_id'];
                $customer_profit = $t['customer_profit'];

                $remarks =" $payee_id has earned customer profit amounting of ".$customer_profit." from order ". $t['transaction_id'];
                $this->log($remarks);

                $this->insertPayout(
                    $payee_id,
                    $t['user_id'],
                    0,
                    0,
                    $customer_profit,
                    $remarks,
                    $t['transaction_id'],
                    0,
                    $t['sponsor_id']
                );
            }
        }

    }

    private function getQualifiedConsultants()
    {

        $start_date = $this->getPeriodStartDate();
        $end_date   = $this->getPeriodEndDate();

        $sql = "
            SELECT 
            u.id AS user_id,
            u.sponsorid,
            u.active
            FROM users u
            JOIN cm_daily_ranks cdr ON u.id = cdr.user_id
            WHERE u.levelid = 3
                AND cdr.is_system_active = 1
                AND u.active = 'Yes'
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getTransactions($sponsor_id)
    {
        $start_date = $this->getPeriodStartDate();
        $end_date   = $this->getPeriodEndDate();
        $membership_products = "19,16";

        $sql = "
            SELECT  
                tp.transaction_id,          
                t.sponsor_id,
                t.user_id,
                SUM(tp.computed_customer_profit) AS customer_profit
            FROM transaction_products tp
            JOIN oc_product op ON  tp.shoppingcart_product_id = op.product_id 
            JOIN v_cm_transactions t ON tp.transaction_id = t.transaction_id            
            WHERE (tp.shoppingcart_product_id NOT IN(19,16) AND op.is_giftcard != 1)
                AND DATE(t.transaction_date) BETWEEN :start_date AND :end_date
                AND t.sponsor_id = :sponsorid
            GROUP BY tp.transaction_id
        ";

        $stmt = $this->db->prepare($sql); 
        $stmt->bindParam(':sponsorid', $sponsor_id); 
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);          
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
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