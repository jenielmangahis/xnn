<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Commissions\Member\Rewards;
use Illuminate\Support\Facades\Auth;

class RewardsController extends Controller 
{
    protected $rewards;

    public function __construct(Rewards $rewards) 
    {
        $this->rewards = $rewards;
    }

    public function giftCards(Request $request)
    {
        return response()->json(
            $this->rewards->getGiftCards($request->all(), Auth::user()->id)
        );
    }

    public function coupons(Request $request)
    {
        return response()->json(
            $this->rewards->getCoupons($request->all(), Auth::user()->id)
        );
    }

}
