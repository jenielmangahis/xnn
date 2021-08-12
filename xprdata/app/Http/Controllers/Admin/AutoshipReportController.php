<?php

namespace App\Http\Controllers\Admin;

use Commissions\Admin\AutoshipReport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

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
                $request->input("year_month")
            ),
        ]);
    }

    public function successfulAutoshipAmount(Request $request)
    {
        return response()->json([
            'amount' => $this->auto_ship->getSuccessfulAutoshipAmount(
                $request->input("year_month")
            )
        ]);
    }

    public function failedAutoshipAmount(Request $request)
    {
        return response()->json([
            'amount' => $this->auto_ship->getFailedAutoshipAmount(
                $request->input("year_month")
            )
        ]);
    }

    public function membersCount(Request $request)
    {
        return response()->json([
            'count' => $this->auto_ship->getMembersCount(
                $request->input("year_month")
            )
        ]);
    }

    public function activeMembersOnAutoshipCount(Request $request)
    {
        return response()->json([
            'count' => $this->auto_ship->getActiveMembersOnAutoshipCount(
                $request->input("year_month")
            )
        ]);
    }

    public function cancelledAutoshipCount(Request $request)
    {
        return response()->json([
            'count' => $this->auto_ship->getCancelledAutoshipCount(
                $request->input("year_month")
            )
        ]);
    }

    public function averageOrderValue(Request $request)
    {
        return response()->json([
            'amount' => $this->auto_ship->getAverageOrderValue(
                $request->input("year_month")
            )
        ]);
    }

    public function pendingAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getPendingAutoship($request->all())
        );
    }

    public function successfulAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getSuccessfulAutoship($request->all())
        );
    }

    public function failedAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getFailedAutoship($request->all())
        );
    }

    public function cancelledAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getCancelledAutoship($request->all())
        );
    }

    public function activeMembersOnAutoship(Request $request)
    {
        return response()->json(
            $this->auto_ship->getActiveMembersOnAutoship($request->all())
        );
    }

}
