<?php

namespace Commissions\Member;

use App\GiftCards;
use App\RerunHostessRewardLog;
use App\Reward;
use App\RewardDiscount;
use Carbon\Carbon;
use Commissions\Console;
use Commissions\Exceptions\AlertException;
use Illuminate\Support\Facades\DB as DB;
use \PDO;
use DateTime;

class HostessRewards extends Console
{

    protected $db;
    protected $end_date;
    protected $customers;
    protected $is_rerun = false;
    protected $is_from_customer_dashboard = false;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->customers = config('commission.member-types.hostess');
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

    public function processRewards($user_id = null)
    {
        $qualified_customers = $this->getQualifiedHostess($this->end_date, $user_id);

        if(empty($qualified_customers)) $this->log("No Qualified Hostess!");

        foreach($qualified_customers as $qualified_customer) {

            $hostess_id = $qualified_customer['hostess_id'];
            $this->log("Processing Hostess Sponsor ID " . $hostess_id);

            $period_start = $qualified_customer['start_date'];
            $period_end = $qualified_customer['end_date'];
            $this->log("Periods: " . $period_start . ' - ' . $period_end);

            $total_sales = $qualified_customer['total_sales'];
            $this->log("Total Sales " . $total_sales);

            $rewards = $total_sales * 0.1; //10% of the total purchases


            $reward = Reward::where("hostess_program_id", $qualified_customer['hostess_program_id'])->first();

            //$gift_cards = json_decode($this->generateGiftCard($hostess_id, $discount, $no_fifty_off));
//            $this->log("Receives $" . $discount . " Gift Card and " . $no_fifty_off . " 50% off");
//            $this->log("Generated rewards " . $gift_cards->{'gc_id'} . " and " . $gift_cards->{'rd_id'});

            //insert rewards
//            $this->insertRewards($hostess_id, $qualified_customer['hostess_program_id'], $discount, $gift_cards->{'rd_id'}, $gift_cards->{'gc_id'}, $total_sales, $qualified_customer['order_ids']);
        }

    }

    public function getQualifiedHostess($end_date, $user_id = null)
    {
        $and = "";

        if($user_id != null) {
            $user_id = +$user_id;
            $and = " AND hp.user_id = $user_id";
        }

        $customer = $this->customers;
        $sql = "
                SELECT 
                    hp.`user_id` AS hostess_id, 
                    SUM(t.`computed_cv`) AS total_sales, 
                    hp.`start_date`, hp.`end_date`, 
                    GROUP_CONCAT(t.`transaction_id`) AS order_ids, 
                    hp.id AS hostess_program_id
                FROM cm_hostess_program hp
                JOIN v_cm_transactions t ON (t.`user_id` = hp.`user_id` OR t.sponsor_id = hp.`user_id`) AND t.`transaction_date` BETWEEN hp.`start_date` AND hp.`end_date`
                WHERE FIND_IN_SET(t.`sponsor_id`, '$customer') AND FIND_IN_SET(t.purchaser_catid, '$customer')
                    AND t.is_replicated_cart_order = 1
                    AND hp.is_deleted = 0
                    AND hp.`end_date` = '$end_date'
                    $and
                GROUP BY hp.`sponsor_id`;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }
}