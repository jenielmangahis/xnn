<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Commissions\Member\DetailedCommission;

class DetailedCommissionController extends Controller
{
    protected $detailed_commission;

    public function __construct(DetailedCommission $detailedCommission)
    {
        $this->detailed_commission = $detailedCommission;
    }

    public function getReport(Request $request)
    {
        return response()->json(
            $this->detailed_commission->getDetailedCommission($request->all(), Auth::user()->id)
        );
    }

    public function download(Request $request)
    {
        sleep(2); // test loading
        return response()->json([
            'link' => $this->detailed_commission->getDownloadLink(
                Auth::user()->id,
                $request->input("start_date"),
                $request->input("end_date"),
                $request->input("commission_type_id"),
                $request->input("language")
            )
        ]);
    }
}
