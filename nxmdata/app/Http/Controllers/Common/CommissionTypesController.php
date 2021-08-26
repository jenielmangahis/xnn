<?php


namespace App\Http\Controllers\Common;

use App\CommissionType;
use App\CommissionPeriod;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CommissionTypesController extends Controller
{
    public function frequencies(Request $request)
    {
        $frequencies = CommissionType::where("is_active", 1)
            ->where("payout_type", "cash")
            ->where("run_type", "manual")
            //->groupBy("frequency")
            ->orderByRaw("FIND_IN_SET(frequency, 'weekly,monthly,semi-annual,quarterly,annual') ASC")
            ->orderBy("id")
            ->get();

        $result = [];

        foreach($frequencies->groupBy("frequency") as $frequency => $commission_type) {
            $result[] = [
                'name' => $frequency,
                'commission_types' => $commission_type
            ];
        }

        return response()->json(
            $result
        );
    }

    public function activeCashManual(Request $request)
    {
        return response()->json(
            CommissionType::where('is_active', 1)->where('payout_type', 'cash')->where('run_type', 'manual')->get()
        );
    }

    public function openPeriods($id)
    {
        return response()->json(
            CommissionPeriod::where('commission_type_id', $id)->where('is_locked', 0)->orderBy("start_date", "asc")->get()
        );

    }

    public function lockedPeriods($id)
    {
        return response()->json(
            CommissionPeriod::where('commission_type_id', $id)->where('is_locked', 1)->get()
        );
    }
}