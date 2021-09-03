<?php


namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Commissions\Member\HistoricalCommission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HistoricalCommissionController extends Controller
{
    protected $historical_commission;

    public function __construct(HistoricalCommission $historical_commission)
    {
        $this->historical_commission = $historical_commission;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->historical_commission->getHistoricalCommission(Auth::user()->id, $request->all())
        );
    }

    public function download(Request $request)
    {
        sleep(2); // test loading
        return response()->json([
            'link' => $this->historical_commission->getDownloadLink(
                Auth::user()->id,
                $request->input("start_date"),
                $request->input("end_date"),
                $request->input("commission_type_id"),
                $request->input("invoice")
            )
        ]);
    }

    public function total(Request $request)
    {
        return response()->json([
            'total' => $this->historical_commission->getTotalAmount(
                Auth::user()->id,
                $request->input("start_date"),
                $request->input("end_date"),
                $request->input("commission_type_id"),
                $request->input("invoice")
            )
        ]);
    }

}