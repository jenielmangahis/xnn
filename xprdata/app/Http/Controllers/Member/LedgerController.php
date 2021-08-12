<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\Ledger\TransferRequest;
use App\Http\Requests\Member\Ledger\WithdrawalRequest;
use App\HyperwalletUser;
use App\IPayoutUser;
use App\PayQuickerUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Member\Ledger;
use Illuminate\Support\Facades\Auth;

class LedgerController extends Controller
{
    protected $ledger;

    public function __construct(Ledger $ledger)
    {
        $this->ledger = $ledger;
    }

    public function ledger(Request $request)
    {
        return response()->json(
            $this->ledger->ledger(
                $request->all(),
                Auth::user()->id
            )
        );
    }

    public function withdrawal(Request $request)
    {
        return response()->json(
            $this->ledger->withdrawal(
                $request->all(),
                Auth::user()->id
            )
        );
    }

    public function transfer(TransferRequest $request)
    {
        $data = $request->only('member_id', 'amount');

        return response()->json(
            $this->ledger->transfer(
                Auth::user()->id,
                $data['amount'],
                $data['member_id']
            )
        );
    }

    public function withdraw(WithdrawalRequest $request)
    {
        $data = $request->only('amount');

        return response()->json(
            $this->ledger->withdraw(
                Auth::user()->id,
                $data['amount']
            )
        );
    }

    public function totalBalance()
    {
        return response()->json([
            'total_balance' => $this->ledger->getTotalBalance(
                Auth::user()->id
            )
        ]);
    }

    public function hadSignup()
    {
        switch (config('commission.payment')) {
            case 'hyperwallet':
                $user = HyperwalletUser::where('user_id', Auth::user()->id)->first();
                break;
            case 'payquicker':
                $user = PayQuickerUser::where('user_id', Auth::user()->id)->first();
                break;
            case 'ipayout':
                $user = IPayoutUser::where('user_id', Auth::user()->id)->first();
                break;
            default:
                $user = null;
        }

        $had_signup = +($user != null);

        return response()->json(compact('had_signup'));
    }

}
