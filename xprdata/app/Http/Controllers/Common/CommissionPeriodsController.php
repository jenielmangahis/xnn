<?php


namespace App\Http\Controllers\Common;

use App\CommissionPeriod;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionPeriodsController extends Controller
{
    public function lockedDates(Request $request)
    {
        $query = DB::table("cm_commission_periods AS pr")
            ->join("cm_commission_types AS t", "t.id", "=", "pr.commission_type_id")
            ->selectRaw("
                pr.start_date,
                pr.end_date,
                pr.start_date AS display_start_date,
                pr.end_date AS display_end_date
            ")
            ->where("pr.is_locked", 1)
            ->orderBy("pr.start_date", "desc");

        if(!!$request->input("frequency")) {
            $query->where("t.frequency", $request->input("frequency"));
        }

        $query->groupBy("pr.start_date", "pr.end_date");

        return response()->json(
            $query->get()
        );
    }
}