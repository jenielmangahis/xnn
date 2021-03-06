<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 8/6/2019
 * Time: 8:42 AM
 */

namespace Commissions\CommissionTypes;

use App\CommissionPeriod;
use App\GiftCard;
use App\OfficeGiftCard;
use App\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SilverStartUp
{
    const FIRST_NINETY_DAYS     = 90;
    const FIRST_THIRTHY_DAYS    = 30;

    const GC_AMOUNT                 = 50;
    const GC_SPONSORED_REP_AMOUNT   = 100;

    const MINIMUM_PRS = 500;

    const CACHE_ID          = 'SSU_LAST_TRANSACTION';
    const GC_REP_NAME       = 'Silver Start Up - 90 Days';
    const GC_SPONSORED_NAME = 'Silver Start Up - Sponsoring';

    private $period_id;

    public function count()
    {
        return count($this->getUsers());
    }

    public function run()
    {
        $commission_type_id = config('commission.commission-types.silver-start-up');
        $period = CommissionPeriod::where('commission_type_id', $commission_type_id)
            ->whereRaw('start_date = CURRENT_DATE()')
            ->first();

        $this->log(json_encode($period));
        $this->setPeriodID($period->id);
        $this->generateCommission();
    }

    public function generateCommission()
    {
        $last_update = $this->getLastTransactionUpdateFromCache();

        if($this->hasTransactionUpdate($last_update) <= 0)
        {
            $this->log('No new transaction to generate commission. Exiting...');
            return;
        }

        $this->setLastTransactionUpdateFromCache($this->setLastTransactionUpdateFromDB());


        // TODO: Implement generateCommission() method.
        $users = $this->getUsers();
        $member_label = config('commission.affiliate');
        $qualified_sponsor_bonus = [];

        $period_id = $this->getPeriodID();

        foreach($users as $user)
        {
            $this->log('Checking PRS of '. $member_label .'. PRS:'. $user->prs);
            $user_id = $user->user_id;

            $num_of_gift_cards      = $this->getGiftCardsToGiveAway($user->prs);
            $num_issued_gift_cards  = $this->getGivenGiftCardCount($user_id, self::GC_REP_NAME);

            $total_gift_cards_to_give = $num_of_gift_cards - $num_issued_gift_cards;

            $this->log(' Number of gift cards to give away '. $num_of_gift_cards);
            $this->log(' Number of issued of gift cards '. $num_issued_gift_cards);
            $this->log(' Number of gift cards to issue'. $total_gift_cards_to_give);

            if($user->num_days_as_affiliate <= self::FIRST_THIRTHY_DAYS
                && $user->num_days_as_affiliate_sponsor <= self::FIRST_NINETY_DAYS)
            {
                if(isset($qualified_sponsor_bonus[$user->sponsorid]))
                    $qualified_sponsor_bonus[$user->sponsorid] += 1;
                else
                    $qualified_sponsor_bonus[$user->sponsorid] = 1;

            }

            if($total_gift_cards_to_give <= 0) continue;

            $gc = [
                'user_id' => $user_id,
                'sponsor_id'=> $user_id,
                'commission_period_id'=>$period_id,
                'amount'=>self::GC_AMOUNT,
                'source'=>self::GC_REP_NAME,
                'rank_id'=> ''
            ];

            for($i=1; $i<= $total_gift_cards_to_give; $i++)
            {
                $gift_card  = $this->addPayoutsGiftCards($gc);
                $office_gc  = $this->addOfficeGiftCard($gift_card);

                $gift_card->code = $office_gc->code;
                $gift_card->update();
            }

        }

        $this->log('Number of qualified sponsor: ' . count($qualified_sponsor_bonus));

        foreach($qualified_sponsor_bonus as $sponsor_id => $num_sponsored_representative)
        {
            $issued_sponsoring_bonus = $this->getGivenGiftCardCount($sponsor_id, self::GC_SPONSORED_NAME);
            $total_gift_cards_to_give = $num_sponsored_representative;

            if(+$issued_sponsoring_bonus > 0)
            $total_gift_cards_to_give = $num_sponsored_representative - $issued_sponsoring_bonus;

            $this->log(' Number of gift cards to give away '. $num_sponsored_representative);
            $this->log(' Number of issued of gift cards '. $issued_sponsoring_bonus);
            $this->log(' Number of gift cards to issue '. $total_gift_cards_to_give);
            if($total_gift_cards_to_give > 0)
            {
                $this->log(' Giving GC to '. $sponsor_id);
                $gc_sponsoring = [
                    'user_id' => $sponsor_id,
                    'sponsor_id'=> $sponsor_id,
                    'commission_period_id'=>$period_id,
                    'amount'=>self::GC_SPONSORED_REP_AMOUNT,
                    'source'=>self::GC_SPONSORED_NAME,
                    'rank_id'=> ''
                ];

                for($i=1; $i<= $total_gift_cards_to_give; $i++)
                {
                    $gift_card  = $this->addPayoutsGiftCards($gc_sponsoring);
                    $office_gc  = $this->addOfficeGiftCard($gift_card);

                    $gift_card->code = $office_gc->code;
                    $gift_card->update();
                }
            }
        }
    }

    public function getUsers($start=null, $length=null, $num_of_days = null)
    {
        if($num_of_days === null) $num_of_days = self::FIRST_NINETY_DAYS;

        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');
        $u = DB::table('cm_affiliates as a')
            ->selectRaw(
                "
                    a.user_id,
                    DATEDIFF(CURRENT_DATE, a.affiliated_at) as num_days_as_affiliate, 
                    DATEDIFF(CURRENT_DATE, au.affiliated_at) as num_days_as_affiliate_sponsor, 
                    u.sponsorid,
                    (SELECT 
                        SUM(COALESCE(t.computed_cv, 0)) 
                    FROM v_cm_transactions t
                    WHERE transaction_date BETWEEN a.affiliated_at AND CURRENT_DATE()
                    AND t.`type` = 'product'
                    AND FIND_IN_SET(t.purchaser_catid, ?)
                    AND t.user_id = a.user_id)
                    + 
                      (SELECT 
                        SUM(COALESCE(ct.computed_cv, 0)) AS cs 
                      FROM
                        v_cm_transactions ct 
                        JOIN cm_transaction_info ti 
                          ON ti.transaction_id = ct.transaction_id 
                      WHERE ct.transaction_date BETWEEN a.affiliated_at 
                        AND CURRENT_DATE() 
                        AND ct.`type` = 'product' 
                        AND FIND_IN_SET(ct.purchaser_catid, ?) 
                        AND ti.upline_id = a.user_id ) AS prs 
                ", [$affiliates, $customers])
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('cm_affiliates as au', 'u.sponsorid', '=', 'au.user_id')
            ->whereRaw("DATE_ADD(a.affiliated_at, INTERVAL ".$num_of_days." DAY) >= CURRENT_DATE()")
            ->where('u.active', 'Yes')
            ->having('prs', '>=', self::MINIMUM_PRS)
        ;


        if($start !== null && $length !== null)
        {
            $u->skip($start)->take($length);
        }

        return $u->get();
    }



    public function addPayoutsGiftCards($data)
    {
        $g = new GiftCard($data);
        $g->save();

        return $g;
    }

    /*
     * Gets the number of cards issued for
     * */
    private function getGivenGiftCardCount($member_id, $gc_type)
    {
        return OfficeGiftCard::where('userid', $member_id)
            ->where('name', $gc_type)
            ->count();
    }

    /*
     * Calculates the number of gift cards to give away
     * */
    private function getGiftCardsToGiveAway($prs)
    {
        return (int) ($prs / self::MINIMUM_PRS);
    }

    private function setPeriodID($period_id)
    {
        $this->period_id = $period_id;
    }
    private function getPeriodID()
    {
        return $this->period_id;
    }


    public function log($msg){
        $datetime = new \DateTime("now", new \DateTimeZone("Asia/Singapore"));
        $path = storage_path("logs/run_commission/");
        $logfile = $path.'SilverStartUp_'.date("Y_m_d").".log";
        file_put_contents($logfile, $datetime->format("Y-m-d H:i:s").": ".$msg."\r\n", FILE_APPEND);
    }

    private function hasTransactionUpdate($last_update)
    {
        $t =  Transaction::validTransactions();
        if($last_update != '')
            $t->where('updated_at', '>', $last_update);


        $t->orderBy('updated_at', 'DESC')
            ->get();

        return $t->count();
    }

    private function getLastTransactionUpdateFromCache()
    {
        return Cache::get(self::CACHE_ID);
    }

    private function setLastTransactionUpdateFromCache($last_update)
    {

        $this->log('Setting last transaction to: ' .$last_update);
        Cache::forever(self::CACHE_ID, $last_update);
    }

    private function setLastTransactionUpdateFromDB()
    {

        return Transaction::validTransactions()
            ->orderBy('updated_at', 'DESC')
            ->first()
            ->updated_at;
    }



    private function addOfficeGiftCard(GiftCard $gift_card)
    {
            $gc = new OfficeGiftCard();
            $gc->name = $gift_card->source;
            $gc->validationcode = OfficeGiftCard::generateRandomString();
            $gc->status = 1;
            $gc->email = $gift_card->email;
            $gc->amount = $gift_card->amount;
            $gc->balance = $gift_card->amount;
            $gc->userid = $gift_card->user_id;
            $gc->end_date = null;
            $gc->save();

            return $gc;
    }

}