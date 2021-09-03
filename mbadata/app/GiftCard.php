<?php


namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GiftCard extends Model
{
    protected $table = "cm_gift_cards";

    public function scopeOfPeriod($query, $period_id)
    {
        return $query->where('commission_period_id', $period_id);
    }

    public static function deleteByPeriod($period_id)
    {
        return static::ofPeriod($period_id)->delete();
    }

    public static function generateOfficeGiftCardsByPeriod($period_id)
    {
        try {
            $gift_cards = DB::table("cm_gift_cards AS gc")
                ->join("users AS u", "u.id", "=", "gc.sponsor_id")
                ->selectRaw("
                    gc.id,
                    gc.sponsor_id,
                    gc.amount,
                    gc.source,
                    u.email
                ")
                ->where("gc.commission_period_id", $period_id)
                ->get();

            foreach ($gift_cards as $gift_card) {

                // $this->info(print_r($gift_card, true));
                $gc = new OfficeGiftCard();
                $gc->name = $gift_card->source;
                $gc->validationcode = OfficeGiftCard::generateRandomString();
                $gc->status = 1;
                $gc->email = $gift_card->email;
                $gc->amount = $gift_card->amount;
                $gc->balance = $gift_card->amount;
                $gc->userid = $gift_card->sponsor_id;
                $gc->end_date = date('Y-m-d', strtotime('+60 day'));
                $gc->save();

                DB::table("cm_gift_cards AS gc")->where("id", $gift_card->id)->update(['code' => $gc->code]);
            }

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}