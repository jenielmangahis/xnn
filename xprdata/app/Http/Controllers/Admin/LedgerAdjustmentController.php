<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LedgerAdjustment\SaveRequest;
use Commissions\Admin\LedgerAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LedgerAdjustmentController extends Controller
{
    protected $ledger_adjustment;

    public function __construct(LedgerAdjustment $ledger_adjustment)
    {
        $this->ledger_adjustment = $ledger_adjustment;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->ledger_adjustment->getAdjustments($request->all())
        );
    }

    public function save(SaveRequest $request)
    {
        return response()->json(
            $this->ledger_adjustment->saveAdjustment(
                $request->input('user_id'),
                $request->input('notes'),
                $request->input('amount'),
                $request->input('type'),
                Auth::user()->id
            )
        );
    }

    public function delete(Request $request, $id)
    {
        return response()->json(
            $this->ledger_adjustment->deleteAdjustment(
                $id,
                Auth::user()->id
            )
        );
    }
}