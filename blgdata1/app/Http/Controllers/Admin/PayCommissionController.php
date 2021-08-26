<?php

namespace App\Http\Controllers\Admin;

use Commissions\Admin\PayCommission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PayCommissionController extends Controller
{
    protected $pay_commission;

    public function __construct(PayCommission $pay_commission)
    {
        $this->pay_commission = $pay_commission;
    }

    public function lockedPeriods(Request $request)
    {
        return response()->json($this->pay_commission->getLockedPeriods($request->input('ids')));
    }

    public function payouts(Request $request)
    {
        return response()->json($this->pay_commission->getPayouts($request->input('ids')));
    }

    public function history(Request $request)
    {
        return response()->json($this->pay_commission->getHistory($request->all()));
    }

    public function total(Request $request)
    {
        return response()->json($this->pay_commission->getTotal($request->input('ids')));
    }

    public function start(Request $request)
    {
        set_time_limit(0);
        $user_id = Auth::user()->id;
        return response()->json($this->pay_commission->start(
            $request->input('ids'),
            $user_id,
            $request->input('period_ids')
        ));
    }

    public function pay(Request $request)
    {
        set_time_limit(0);
        return response()->json($this->pay_commission->pay(
            $request->input('ids'),
            $request->input('user_id'),
            $request->input('period_ids'),
            $request->input('history_id')
        ));
    }

    public function log(Request $request, $id)
    {
        return response()->json($this->pay_commission->log(
            $id,
            $request->input('seek')
        ));
    }

    public function paymentDetails(Request $request)
    {
        return response()->json($this->pay_commission->getPaymentDetails(
            $request->all()
        ));
    }

    public function markAsPaid(Request $request)
    {
        $user_id = Auth::user()->id;
        return response()->json($this->pay_commission->markAsPaid(
            $request->input('ids'),
            $user_id
        ));
    }
}
