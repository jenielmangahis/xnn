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

    public function currentPeriodOrders(Request $request)
    {
        return response()->json(
            $this->dashboard->getCurrentPeriodOrders(Auth::user()->id, $request->all())
        );
    }

    public function giftCards(Request $request)
    {
        return response()->json(
            $this->dashboard->getGiftCards(Auth::user()->id, $request->all())
        );
    }

    public function titleAchievementBonusDetails(Request $request)
    {
        return response()->json(
            $this->dashboard->getTitleAchievementBonusDetails(Auth::user()->id)
        );
    }

    public function getPEA(Request $request)
    {

        $filters = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => [],
            'order' => [],
            'columns' => [],
        ];

        $filters = array_merge($filters, $request->all());

        return response()->json(
            $this->dashboard->getPEA(Auth::user()->id, $filters)
        );
    }

    public function getPEAStatusHistory(Request $request)
    {
        return response()->json(
            $this->dashboard->getPEAStatusHistory(Auth::user()->id, $request)
        );
    }

    public function getLastThreeMonthsEarnings(Request $request)
    {
        return response()->json(
            $this->dashboard->getLastThreeMonthsEarnings(Auth::user()->id)
        );
    }

    public function getQualificationRequirementDetails(Request $request)
    {
        return response()->json(
            $this->dashboard->getQualificationRequirementDetails(Auth::user()->id)
        );
    }

}