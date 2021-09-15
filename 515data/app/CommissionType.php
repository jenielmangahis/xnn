<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CommissionType extends Model
{
    const RUN_TYPE_MANUAL = "manual";
    const RUN_TYPE_AUTO = "auto";

    const PAYOUT_TYPE_CASH = "cash";
    const PAYOUT_TYPE_COUPON = "coupon";
    const PAYOUT_TYPE_OTHER = "other";

    protected $table = 'cm_commission_types';

    public function commissionPeriods()
    {
        return $this->hasMany(CommissionPeriod::class, 'commission_type_id', 'id');
    }

    public static function generateCommissionPeriods()
    {
        $commission_types = static::where('is_active', 1)->orWhere('id', 1)->get();

        foreach ($commission_types as $commission_type) {

            $commission_period = CommissionPeriod::where('commission_type_id', $commission_type->id)
                ->orderBy('start_date', 'desc')->first();

            $is_locked = 0;

            if($commission_type->frequency == 'other') {
                $is_locked = 1;
                $first_day_of_the_week = config("commission.first_day_of_the_week", "monday");
                $start_date = $commission_period == null ?
                    date( 'Y-m-d', strtotime( "$first_day_of_the_week this week"  ) ):
                    date("Y-m-d", strtotime("next $first_day_of_the_week", strtotime($commission_period->start_date)));

                $end_date = Carbon::createFromFormat("Y-m-d", $start_date)->adddays(6)->format("Y-m-d");
            }
            elseif($commission_type->frequency == 'annual')  {
                $start_date = $commission_period == null ?
                    date('Y-01-01') :
                    date("Y-01-01", strtotime("next year", strtotime($commission_period->start_date)));

                $end_date = Carbon::createFromFormat("Y-m-d", $start_date)->lastOfYear()->format("Y-m-d");
            }
            else if($commission_type->frequency == 'quarterly') {
                $start_date = $commission_period == null ?
                    date("Y-" . ["01","04","07","10"][ceil(date("m")/3) - 1] . "-01") :
                    date("Y-m-01", strtotime("+3 months", strtotime($commission_period->start_date)));

                $end_date = Carbon::createFromFormat("Y-m-d",$start_date)->lastOfQuarter()->format("Y-m-d");
            }
            else if($commission_type->frequency == 'monthly') {
                $start_date = $commission_period == null ?
                    date('Y-m-01') :
                    date("Y-m-01", strtotime("+1 month", strtotime($commission_period->start_date)));

                $end_date = Carbon::createFromFormat("Y-m-d", $start_date)->lastOfMonth()->format("Y-m-d");
            }
            else if($commission_type->frequency == 'weekly') {
                $first_day_of_the_week = config("commission.first_day_of_the_week", "monday");
                $start_date = $commission_period == null ?
                    date( 'Y-m-d', strtotime( "$first_day_of_the_week this week"  ) ):
                    date("Y-m-d", strtotime("next $first_day_of_the_week", strtotime($commission_period->start_date)));

                $end_date = Carbon::createFromFormat("Y-m-d", $start_date)->adddays(6)->format("Y-m-d");
            } else if($commission_type->frequency == 'daily') {
                $start_date = $commission_period == null ?
                    date("Y-m-d"):
                    date("Y-m-d", strtotime("tomorrow", strtotime($commission_period->start_date)));

                $end_date = $start_date;
            }

            $commission_period = CommissionPeriod::where('commission_type_id', $commission_type->id)->where('start_date', $start_date)->first();

            if (strtotime(date("Y-m-d")) >= strtotime($start_date) && $commission_period === null) {
                $commission_period = new CommissionPeriod();
                $commission_period->commission_type_id = $commission_type->id;
                $commission_period->start_date = $start_date;
                $commission_period->end_date = $end_date;
                $commission_period->is_locked = $is_locked;

                if($is_locked === 1) {
                    $commission_period->locked_at = Carbon::now();
                }

                $commission_period->save();
            }
        }
    }
}
