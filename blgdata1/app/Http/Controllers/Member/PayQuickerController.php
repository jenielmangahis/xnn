<?php


namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\PayQuicker\SignUpRequest;
use Commissions\Member\PayQuicker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayQuickerController extends Controller
{
    protected $pay_quicker;

    public function __construct(PayQuicker $pay_quicker)
    {
        $this->pay_quicker = $pay_quicker;
    }

    public function getUser(Request $request)
    {
        return response()->json(
            $this->pay_quicker->getUser(Auth::user()->id)
        );
    }

    public function signUp(SignUpRequest $request)
    {
        return response()->json(
            $this->pay_quicker->createUser(Auth::user()->id, $request->all())
        );
    }
}