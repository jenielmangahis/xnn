<?php


namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Commissions\Member\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    public function currentRankDetails(Request $request)
    {
        return response()->json(
            $this->dashboard->getCurrentRankDetails(Auth::user()->id)
        );
    }
    public function currentBinaryDetails(Request $request)
    {
        return response()->json(
            $this->dashboard->getCurrentBinaryVolumeDetails(Auth::user()->id)
        );
    }
    public function currentEarningsDetails(Request $request)
    {
        return response()->json(
            $this->dashboard->getEarningsDetails(Auth::user()->id)
        );
    }

    public function currentPeriodOrders(Request $request)
    {
        return response()->json(
            $this->dashboard->getCurrentPeriodOrders(Auth::user()->id, $request->all())
        );
    }
}