<?php

namespace Commissions\Member;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB as DB;
use \PDO;
use DateTime;
use App\OfficeGiftCard;
use App\OfficeReward;
use App\User;

class HostessRewards
{
    
    const MINIMUM_SALES_REQUIREMENT = 500;

    protected $db;
    protected $end_date;
    protected $customers;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->customers = config('commission.member-types.customers');
    }

    public function runRewards()
    {
        $end_date = Carbon::yesterday();
        $this->end_date = $end_date->format("Y-m-d");

        DB::transaction(function () {

            $this->log("Running Hostess Rewards for " . $this->end_date);
            $this->processRewards();

        }, 3);

    }

    public function processRewards()
    {
        $qualified_customers = $this->getQualifiedHostess($this->end_date);

        if(empty($qualified_customers)) return $this->log("No Qualified Hostess!");

        foreach($qualified_customers as $qualified_customer) {

            $hostess_id = $qualified_customer['hostess_id'];
            $this->log("Processing Hostess ID " . $hostess_id);

            $period_start = $qualified_customer['start_date'];
            $period_end = $qualified_customer['end_date'];
            $this->log("Periods: " . $period_start . ' - ' . $period_end);

            $total_sales = $qualified_customer['total_sales'];
            $this->log("Total Sales " . $total_sales);

            $rewards = $this->getRewards($total_sales);

            //get discount
            $discount = $total_sales * ($rewards['discount'] / 100);
            $no_fifty_off = $rewards['no_fifty_off'];
            // $this->log("Receives $" . $discount . " Gift Card and " . $no_fifty_off . " 50% off");

            $gift_cards = $this->generateGiftCard($hostess_id, $discount, $no_fifty_off);
            $this->log("Receives $" . $discount . " Gift Card and " . $no_fifty_off . " 50% off");
            $this->log("Generated rewards " . $gift_cards['gc'] . " and " . $gift_cards['rc']);

            //insert rewards
            $this->insertRewards($hostess_id, $qualified_customer['hostess_program_id'], $discount, $gift_cards['rc'], $gift_cards['gc'], $total_sales, $qualified_customer['order_ids']);
        }
        
    }

    public function getQualifiedHostess($end_date)
    {
        $sql = "
                SELECT 
                    hp.`user_id` AS hostess_id, SUM(t.`computed_cv`) AS total_sales
                    , hp.`start_date`, hp.`end_date`, GROUP_CONCAT(t.`transaction_id`) AS order_ids
                    , hp.id AS hostess_program_id
                    , count(1) as num_orders
                FROM cm_hostess_program hp
                JOIN v_cm_transactions t ON (t.`user_id` = hp.`user_id` OR t.sponsor_id = hp.`user_id`) AND t.`transaction_date` BETWEEN hp.`start_date` AND hp.`end_date`
                WHERE FIND_IN_SET(t.`purchaser_catid`, :customer)
                    AND hp.is_deleted = 0
                    AND hp.`end_date` = :end_date
                GROUP BY hp.`user_id`
                HAVING total_sales >= :minimum_total_sales
                 AND num_orders >=3 
        ";
        $customer = $this->customers;
        $minimum_sales = static::MINIMUM_SALES_REQUIREMENT;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->bindParam(":customer", $customer);
        $stmt->bindParam(":minimum_total_sales", $minimum_sales);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRewards($total_sales)
    {
        $sql = "
                SELECT 
                    discount, no_fifty_off
                FROM cm_rewards_config
                WHERE :total_sales BETWEEN range_from AND range_to;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":total_sales", $total_sales);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertRewards($user_id, $hostess_program_id, $discount, $no_fifty_off, $discount_code, $total_sales, $order_ids) {

        return DB::transaction(function() use ($user_id, $hostess_program_id, $discount, $no_fifty_off, $discount_code, $total_sales, $order_ids) {
            
            $rewards_data = [
                'user_id' => $user_id,
                'hostess_program_id' => $hostess_program_id,
                'discount' => $discount,
                'no_fifty_off' => $no_fifty_off,
                'discount_code' => $discount_code,
                'total_sales' => $total_sales,
                'order_ids' => $order_ids
            ];

            return DB::table('cm_rewards')->insert($rewards_data);
        });
    }

    function generateGiftCard($userid, $discount, $no_off)
    {
        $output = ['gc'=>null, 'rc' => null];
        $user = User::find($userid);

        $gc = new OfficeGiftCard();
        $gc->name = 'HOSTESS Rewards';
        $gc->validationcode = OfficeGiftCard::generateRandomString();
        $gc->status = 1;
        $gc->email = $user->email;
        $gc->amount = $discount;
        $gc->balance = $discount;
        $gc->userid = $user->sponsorid;
        $gc->end_date = date('Y-m-d', strtotime('+90 day'));
        $gc->save();

        $output['gc'] = $gc->code;

        if($no_off > 0)
        {
            //add 50 percent off
            $rc = new OfficeReward();
            $rc->userid = $userid;
            $rc->discount_count = $no_off;
            $rc->save();

            $output['rc'] = $rc->id;
        }

        return $output;


    }

    private function log($message, $time = true)
    {
        if (php_sapi_name() !== 'cli') return;

        if ($time) {
            $t = Carbon::now()->toDateTimeString();
            $message = "[{$t}] - {$message}";
        }

        echo $message . PHP_EOL;
    }
}