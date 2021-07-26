<?php

namespace App\Http\Controllers\Admin;

use Commissions\Admin\LedgerWithdrawal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LedgerWithdrawalController extends Controller
{
    protected $ledger_withdrawal;

    public function __construct(LedgerWithdrawal $ledger_withdrawal)
    {
        $this->ledger_withdrawal = $ledger_withdrawal;
    }

    public function pending(Request $request)
    {
        return response()->json(
            $this->ledger_withdrawal->getPendingRequest(
                $request->input('start_date'),
                $request->input('end_date'))
        );
    }

    public function reject(Request $request)
    {
        return response()->json(
            $this->ledger_withdrawal->rejectRequest(
                $request->input('ids'),
                Auth::user()->id
            )
        );
    }

    public function start(Request $request)
    {
        return response()->json(
            $this->ledger_withdrawal->startProcess(
                $request->input('ids'),
                Auth::user()->id
            )
        );
    }

    public function pay(Request $request)
    {
        set_time_limit(0);
        return response()->json(
            $this->ledger_withdrawal->pay(
                $request->input('history_id')
            )
        );
    }

    public function history(Request $request)
    {
        return response()->json(
            $this->ledger_withdrawal->getHistory(
                $request->all()
            )
        );
    }

    public function paymentDetails(Request $request)
    {
        return response()->json(
            $this->ledger_withdrawal->getPaymentDetails(
                $request->all()
            )
        );
    }

    public function log(Request $request, $history_id)
    {
        return response()->json(
            $this->ledger_withdrawal->getHistoryLog(
                $history_id,
                $request->input("seek")
            )
        );
    }

}
