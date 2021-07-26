<?php

namespace App\Http\Controllers\Member;

use Commissions\Member\IncentiveReport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IncentiveReportController extends Controller
{
    protected $incentive_report;

    public function __construct(IncentiveReport $incentiveReport)
    {
        $this->incentive_report = $incentiveReport;
    }

    public function getAvailableIncentives()
    {
        return response()->json(
            $this->incentive_report->getAvailableIncentive()
        );
    }

    public function getProgress(Request $request)
    {
        return response()->json(
            $this->incentive_report->getProgress($request, Auth::user()->id)
        );
    }
}
