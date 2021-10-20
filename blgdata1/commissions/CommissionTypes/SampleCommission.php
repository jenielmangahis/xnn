<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;
use Illuminate\Support\Facades\DB as DB;

class SampleCommission extends CommissionType implements CommissionTypeInterface
{

    const BONUS_PERCENTAGE = 0.1;
    
    public function count()
    {
        return count($this->getOrders());
    }

    public function generateCommission($start, $length)
    {
        $orders = $this->getOrders($start, $length);

        foreach ($orders as $key => $order) {
            $this->log("Processing Order ID " . $order['order_id']);

            if($key <= 15) {
                sleep(1);
            }

            $this->insertPayout(
                $order['sponsor_id'],
                $order['user_id'],
                $order['cv'],
                static::BONUS_PERCENTAGE,
                $order['cv'] * static::BONUS_PERCENTAGE,
                "Put other info here that are relevant to the commission type like ranks, volumes, paid as ranks, leg count, etc.",
                $order['order_id'],
                0
            );

//            DB::table('cm_gift_cards')->insert([
//                'user_id' => 20,
//                'sponsor_id'=> 20,
//                'commission_period_id'=> $this->getPeriodId(),
//                'amount' => 1,
//                'source' => "Sample Commission",
//                'rank_id' => 1
//            ]);

            if($key === 40) {
                // throw new \Exception("OMG");
            }

            $this->log(); // For progress bar. Put this every end of the loop.
        }

    }

    private function getOrders($start = null, $length = null)
    {
        $orders = [];

        if($start !== null) {
            $counter = $start;
            $length = $start + $length;
        } else {
            $counter = 0;
            $length = 2;
        }

        for(; $counter < $length; $counter++) {
            $orders[] = [
                'order_id' => $counter,
                'cv' => mt_rand (1*10, 3*10) / 10,
                'sponsor_id' => 20,
                'user_id' => 20,
                'transaction_date' => $this->getPeriodEndDate()
            ];
        }

        return $orders;
    }
}