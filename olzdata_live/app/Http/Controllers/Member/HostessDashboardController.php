<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Member\HostessDashboard;
use Illuminate\Support\Facades\Auth;

class HostessDashboardController extends Controller
{
    protected $hostess_dashboard;

    public function __construct(HostessDashboard $hostess_dashboard)
    {
        $this->hostess_dashboard = $hostess_dashboard;
    }

    public function getRewardCountdown()
    {
        return response()->json(
            $this->hostess_dashboard->getCountdown(Auth::user()->id)
        );
    }

    public function getRewardProgress()
    {
        return response()->json(
            $this->hostess_dashboard->getAmount(Auth::user()->id)
        );
    }

    public function getOrders($id)
    {
        return response()->json(
            $this->hostess_dashboard->getOrders($id)
        );
    }

    public function getProductCredits(Request $request)
    {
        return response()->json(
            $this->hostess_dashboard->getProductCredits($request->all(), Auth::user()->id)
        );
    }

    public function getCoupons(Request $request)
    {
        return response()->json(
            $this->hostess_dashboard->getCoupons($request->all(), Auth::user()->id)
        );
    }
    
    public function getSharingLink()
    {
        return response()->json(
            $this->hostess_dashboard->getSharingLink(Auth::user()->id)
        );
    }
    
    public function getOpenEvent(Request $request)
    {
        // EDIT ME
        return response()->json(
            $this->hostess_dashboard->getOpenEvent($request->all(), Auth::user()->id)
        );
    }
    
    public function getDailyReward(Request $request)
    {
        return response()->json(
            $this->hostess_dashboard->getDailyRewards($request->all(), Auth::user()->id)
        );
    }

}
