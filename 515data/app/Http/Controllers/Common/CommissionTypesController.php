<?php


namespace App\Http\Controllers\Common;

use App\CommissionType;
use App\CommissionPeriod;
use App\CommissionGroups;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            CommissionPeriod::where('commission_type_id', $id)->where('is_locked', 0)->get()
        );

    }

    public function lockedPeriods($id)
    {
        return response()->json(
            CommissionPeriod::where('commission_type_id', $id)->where('is_locked', 1)->get()
        );
    }

    public function commissionGroup()
    {
        return response()->json(
            CommissionGroups::all()
        );
    }

    public function groupTypes(Request $request)
    {
        $query = DB::table("cm_commission_types AS ct")
            ->join("cm_commission_group_types AS gt", "gt.type_id", "=", "ct.id")
            ->join("cm_commission_groups AS cg", "cg.id", "=", "gt.group_id");

        $l = 'english';
        $columns = "
                ct.id,
                ct.name
            ";

        if($request->exists('language'))
        {
            $l = $request->get('language');
        }

        if($l == 'italian')
        {
            $columns = "
                ct.id,
                ct.name_italian as name
            ";
        }
        $query = $query->selectRaw($columns);

        if(!!$request->input("frequency") && $request->input("frequency") != 'all') {
            $query->where("cg.name", $request->input("frequency"));
        }

        return response()->json(
            $query->get()
        );
    }
}