<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Member\AutoshipReport;
use Illuminate\Support\Facades\Auth;

class AutoshipReportController extends Controller
{
    protected $auto_ship;

    public function __construct(AutoshipReport $autoshipReport)
    {
        $this->auto_ship = $autoshipReport;
    }

    public function pendingAutoshipAmount(Request $request)
    {
        return response()->json([
            'amount' => $this->auto_ship->getPendingAutoshipAmount(
                $request->input("year_month"),
                Auth::user()->id
            ),
            'date' => $this->auto_ship->getCarbonDate($request->input("year_month"))
        ]);
    }

    public function successfulAutoshipAmount(Request $request)
    {
        return response()->json([
            'amount' => $this->auto_ship->getSuccessfulAutoshipAmount(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function failedAutoshipAmount(Request $request)
    {
        return response()->json([
            'amount' => $this->auto_ship->getFailedAutoshipAmount(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function membersCount(Request $request)
    {
        return response()->json([
            'count' => $this->auto_ship->getMembersCount(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function activeMembersOnAutoshipCount(Request $request)
    {
        return response()->json([
            'count' => $this->auto_ship->getActiveMembersOnAutoshipCount(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function cancelledAutoshipCount(Request $request)
    {
        return response()->json([
            'count' => $this->auto_ship->getCancelledAutoshipCount(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function averageOrderValue(Request $request)
    {
        return response()->json([
            'amount' => $this->auto_ship->getAverageOrderValue(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function personallyEnrolledRetentionRate(Request $request)
    {
        return response()->json([
            'rate' => $this->auto_ship->getPersonallyEnrolledRetentionRate(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function organizationalRetentionRate(Request $request)
    {
        return response()->json([
            'rate' => $this->auto_ship->getOrganizationalRetentionRate(
                $request->input("year_month"),
                Auth::user()->id
            )
        ]);
    }

    public function pendingAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getPendingAutoship($request->all(), Auth::user()->id)
        );
    }

    public function successfulAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getSuccessfulAutoship($request->all(), Auth::user()->id)
        );
    }

    public function failedAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getFailedAutoship($request->all(), Auth::user()->id)
        );
    }

    public function cancelledAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getCancelledAutoship($request->all(), Auth::user()->id)
        );
    }

    public function activeMembersOnAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getActiveMembersOnAutoship($request->all(), Auth::user()->id)
        );
    }
}
