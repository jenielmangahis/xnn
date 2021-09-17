<?php

namespace Commissions\CommissionTypes;

use App\CommissionPeriod;
use App\GiftCard;
use App\OfficeGiftCard;
use Carbon\Carbon;
use Commissions\Console;
use Commissions\Exceptions\AlertException;
use Illuminate\Support\Facades\DB as DB;
use Illuminate\Support\Facades\Cache;
use \PDO;
use DateTime;

class HostessRewards extends Console
{

    protected $db;
    protected $end_date;
    protected $customers;
    private $period_id;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function runRewards()
    {
        $end_date = Carbon::today();
        $this->end_date = $end_date->format("Y-m-d");
        $commission_type_id = config('commission.commission-types.hostess-daily-rewards');
        $period = CommissionPeriod::where('commission_type_id', $commission_type_id)
            ->where("start_date", $this->end_date)
            ->first();
        $this->setPeriodID($period->id);

        DB::transaction(function () {
            $this->log("Running Hostess Rewards for " . $this->end_date);
            $this->processRewards();

        }, 3);

    }

    public function processRewards()
    {
        $qualified_customers = $this->getQualifiedHostess($this->end_date);

        if(empty($qualified_customers)) $this->log("No Qualified Hostess!");

        foreach($qualified_customers as $qualified_customer) {

            $hostess_id = $qualified_customer['hostess_id'];
            $this->log("Processing Hostess ID " . $hostess_id);

            $total_sales = $qualified_customer['total_sales'];
            $this->log("Total Sales " . $total_sales);

            $rewards = $total_sales * 0.1; //10% of the total purchases
            $data =  [
                'user_id' => $hostess_id,
                'sponsor_id'=> $hostess_id,
                'commission_period_id'=> $this->getPeriodID(),
                'amount'=> $rewards,
                'source'=> 'Hostess Daily Rewards',
                'rank_id'=> ''
            ];

            $this->addPayoutsGiftCards($data);
            $this->addOfficeGiftCard($hostess_id,$this->getPeriodID());

        }

    }

    public function getQualifiedHostess($end_date)
    {

        $customer = config('commission.member-types.hostess');
        $sql = "
                SELECT 
                    t.user_id AS hostess_id,
                    t.sponsor_id,
                    SUM(t.`computed_cv`) AS total_sales, 
                    GROUP_CONCAT(t.`transaction_id`) AS order_ids
                FROM v_cm_transactions t
                WHERE FIND_IN_SET(t.`sponsor_catid`, '$customer') AND FIND_IN_SET(t.purchaser_catid, '$customer')
                AND t.is_replicated_cart_order = 1
                AND t.transaction_date = '$end_date'
                AND t.type = 'product'
                AND NOT EXISTS(SELECT 1 FROM cm_hostess_program hp WHERE (hp.user_id = t.user_id OR hp.user_id = t.sponsor_id) AND hp.is_deleted = 0 AND t.transaction_date BETWEEN hp.start_date AND hp.end_date)
                GROUP BY t.sponsor_id;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPayoutsGiftCards($data)
    {
        DB::table('cm_gift_cards')->insert($data);
    }

    private function addOfficeGiftCard($user_id, $commission_peirod_id)
    {
        $gift_cards = DB::table("cm_gift_cards AS gc")
            ->join("users AS u", "u.id", "=", "gc.sponsor_id")
            ->selectRaw("
                    gc.id,
                    gc.sponsor_id,
                    gc.amount,
                    gc.source,
                    u.email,
                    gc.user_id
                ")
            ->where("gc.user_id", $user_id)
            ->where("gc.commission_period_id", $commission_peirod_id)
            ->get();

        foreach ($gift_cards as $gift_card) {

            $gc = new OfficeGiftCard();
            $gc->name = $gift_card->source;
            $gc->validationcode = OfficeGiftCard::generateRandomString();
            $gc->status = 1;
            $gc->email = $gift_card->email;
            $gc->amount = $gift_card->amount;
            $gc->balance = $gift_card->amount;
            $gc->userid = $gift_card->user_id;
            $gc->end_date = date('Y-m-d', strtotime('+90 day')); //90days expiry
            $gc->save();

            DB::table("cm_gift_cards AS gc")->where("id", $gift_card->id)->update(['code' => $gc->code]);
        }

        return $gc;
    }

    private function setPeriodID($period_id)
    {
        $this->period_id = $period_id;
    }

    private function getPeriodID()
    {
        return $this->period_id;
    }

    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }
}