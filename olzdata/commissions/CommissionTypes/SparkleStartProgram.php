<?php


namespace Commissions\CommissionTypes;


use App\User;
use App\GiftCard;
use App\OfficeGiftCard;
use App\CommissionPeriod;
use Commissions\Admin\RunCommission;
use Illuminate\Support\Facades\DB;

class SparkleStartProgram extends RunCommission
{

    const GIFT_CARD = 50;
    protected $period_id;

    public function __construct()
    {
        $this->period_id = 0;
        $this->db = DB::connection()->getPdo();
    }

    public function processSparkelStartProgram()
    {

        DB::transaction(function () {

            $this->getCommissionPeriod();
            $this->init($this->period_id, 1);
            
            $users = $this->getQualifiedUsers();

            if(count($users) > 0) {
                foreach($users as $user) {

                    if($user['prs'] >= 500) {
                        $is_received = $this->isRepresentativeReceivedGiftCard($user['user_id'], $user['sponsorid']);
        
                        if($is_received === 0) {
        
                            $prs = $user['prs'];
                            $user_id = $user['user_id'];

                            $this->addPayoutsGiftCards([
                                'user_id' => $user_id,
                                'sponsor_id'=> $user['sponsorid'],
                                'commission_period_id' => $this->period_id,
                                'amount'=>static::GIFT_CARD,
                                'source'=>"Sparkle Start Program - product rewards gift card",
                                'rank_id'=> $user['paid_as_rank_id'],
                                'code' => null,
                            ]);
                            
                            echo "Representative: $user_id received $50 product rewards (gift card) for having $$prs PRS in the first 10 days\n";

                            $this->addJewelryGiftCard($user); // for upline representative

                        } else {
                            $user_id = $user['user_id'];
                            echo "Skiping Member $user_id - already received $50 product rewards (gift card)\n";
                        }
                    }
                }
            }
            else {
                echo "No Qualified Representatives\n";
            }

            $this->init($this->period_id, 0);
        });
    }

    private function addJewelryGiftCard($user)
    {
        $representative = $this->isRepresentative($user['sponsorid']);

        if(count($representative) > 0) {
            
            $user_id = $representative[0]['user_id'];
            $sponsor_id = $representative[0]['sponsorid'];
            
            $is_received = $this->isRepresentativeReceivedGiftCard($user_id, $sponsor_id);

            if($is_received === 0) {

                $this->addPayoutsGiftCards([
                    'user_id' => $user_id,
                    'sponsor_id' => $sponsor_id,
                    'commission_period_id' => $this->period_id,
                    'amount' =>static::GIFT_CARD,
                    'source' =>"Sparkle Start Program - jewelry gift card",
                    'rank_id' => $user['paid_as_rank_id'],
                    'code' => null
                ]);

                echo "Sponsor: $user_id received $50 jewelry gift card\n";
            } 
            else {
                echo "Skipping Sponsor: $user_id already received $50 jewelry gift card\n";
            }
        }
    }

    private function addPayoutsGiftCards($data)
    {
        $id = DB::table('cm_gift_cards')->insertGetId($data);

        $this->addOfficeGiftCard($id);
    }

    private function addOfficeGiftCard($id)
    {
        $sql = "
            SELECT gc.id,
            gc.user_id,
            gc.amount,
            gc.source,
            u.email
        FROM cm_gift_cards AS gc
        JOIN users AS u ON u.id = gc.user_id
        WHERE gc.`id` = $id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $gift_card = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if(count($gift_card) > 0) {
            $gc = new OfficeGiftCard();
            $gc->name = $gift_card[0]['source'];
            $gc->validationcode = OfficeGiftCard::generateRandomString();
            $gc->status = 1;
            $gc->email = $gift_card[0]['email'];
            $gc->amount = $gift_card[0]['amount'];
            $gc->balance = $gift_card[0]['amount'];
            $gc->userid = $gift_card[0]['user_id'];
            $gc->end_date = null;
            $gc->save();

            DB::table("cm_gift_cards AS gc")->where("id", $id)->update(['code' => $gc->code]);
        }
    }

    private function isRepresentative($user_id)
    {
        $affiliates = config('commission.member-types.affiliates');
        $sql = "
            SELECT u.id as user_id, u.sponsorid FROM users AS u
            WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE u.id = $user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getQualifiedUsers()
    {
        $affiliates = config('commission.member-types.affiliates');
        $sql = "
            SELECT ca.user_id, u.`sponsorid`, dv.`prs`, dv.`volume_date`, dr.`paid_as_rank_id` FROM users u
            JOIN cm_affiliates ca ON u.id = ca.user_id
            JOIN cm_daily_volumes dv ON u.id = dv.user_id AND dv.`volume_date` = CURRENT_DATE()
            JOIN cm_daily_ranks dr ON dv.id = dr.`volume_id` AND dr.`rank_date` = CURRENT_DATE()
            WHERE ca.affiliated_date BETWEEN DATE_SUB(NOW(), INTERVAL 10 DAY) AND NOW()
            AND FIND_IN_SET(ca.cat_id, '$affiliates')
            AND u.`active` = 'Yes'
            ORDER BY dv.user_id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function isRepresentativeReceivedGiftCard($user_id = null, $sponsor_id = null)
    {

        $count = GiftCard::where('user_id', $user_id)
                ->where('sponsor_id', $sponsor_id)
                ->ofPeriod($this->period_id)
                ->count();

        return $count;
    }

    public function getCommissionPeriod()
    {
        $start_date = date('Y-m-d', strtotime("today"));
        $type = config('commission.commission-types.sparkle-start-program');

        $data = CommissionPeriod::where('commission_type_id', $type)
                        ->where('start_date', $start_date)->first();

        if(count($data) > 0 && $data->is_running == 1) {
            throw new \Exception("Period is currently running");
        }

        if(count($data) > 0) {
            $this->period_id = $data->id;
        }
        else {
            $period = new CommissionPeriod();
            $period->commission_type_id = $type;
            $period->start_date = $start_date;
            $period->end_date = $start_date;
            $period->save();
            $this->period_id = $period->id;
        }

        $this->lockPrevCommissionPeriod();
    }

    public function lockPrevCommissionPeriod()
    {
        $start_date = date('Y-m-d', strtotime("yesterday"));
        $type = config('commission.commission-types.sparkle-start-program');

        $data = CommissionPeriod::where('commission_type_id', $type)
                ->where('start_date', $start_date)->first();

        if(count($data) > 0) {
            if($data->is_locked === 0) {
                $commission_period = CommissionPeriod::find($data->id);
                $commission_period->is_locked = 1;
                $commission_period->locked_at = date("Y-m-d h:i");
                $commission_period->save();
            }
        }
    }

    //Commission Run Methods
    public function init($period_id, $is_running = 0)
    {
        DB::table('cm_commission_periods')->where('id', $period_id)->update(['is_running' => $is_running]);
    }

    public function getDownlines($user_id) {

        $affiliates = config('commission.member-types.affiliates');

        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                SELECT 
                id AS user_id,
                sponsorid AS parent_id,
                1 AS `level`
                FROM users
                WHERE sponsorid = $user_id AND levelid = 3
                
                UNION ALL
                
                SELECT
                p.id AS user_id,
                p.sponsorid AS parent_id,
                downline.`level` + 1 `level`
                FROM users p
                INNER JOIN downline ON p.sponsorid = downline.user_id
                WHERE p.levelid = 3 AND p.active = 'Yes'
            )
            SELECT dv.`prs`
            FROM downline d 
            JOIN cm_affiliates ca ON d.user_id = ca.user_id
            JOIN cm_daily_volumes dv ON d.user_id = dv.user_id AND dv.`volume_date` = CURRENT_DATE()
            JOIN cm_daily_ranks dr ON dv.id = dr.`volume_id` AND dr.`rank_date` = CURRENT_DATE()
            WHERE ca.affiliated_date BETWEEN DATE_SUB(NOW(), INTERVAL 10 DAY) AND NOW()
            AND FIND_IN_SET(ca.cat_id, '$affiliates')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function isQualifiedForSparkleStartProgram($user_id) {
        $sql = "
            SELECT ca.user_id, u.`sponsorid`, dv.`prs`, dv.`volume_date`, dr.`paid_as_rank_id` 
            FROM users u
            JOIN cm_affiliates ca ON u.id = ca.user_id
            JOIN cm_daily_volumes dv ON u.id = dv.user_id AND dv.`volume_date` = CURRENT_DATE()
            JOIN cm_daily_ranks dr ON dv.id = dr.`volume_id` AND dr.`rank_date` = CURRENT_DATE()
            WHERE ca.affiliated_date BETWEEN DATE_SUB(NOW(), INTERVAL 10 DAY) AND NOW()
            AND FIND_IN_SET(ca.cat_id, '13')
            AND u.`active` = 'Yes'
            AND u.id = $user_id
            ORDER BY dv.user_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $isQualified = false;
        if(count($result) > 0) {
            $isQualified = true;
        } 
        else {
            $user_downlines = $this->getDownlines($user_id);
            if(count($user_downlines) > 0) {
                foreach($user_downlines as $downline) {
                    if($downline['prs'] >= 500) {
                        $isQualified = true;
                    }
                }
            }
        }

        return $isQualified;
    }
}