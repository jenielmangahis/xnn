<?php

namespace App\Http\Controllers\Member;

use Commissions\Member\IPayout;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IPayoutController extends Controller
{
    protected $ipayout_service;

    public function __construct(IPayout $ipayout_service)
    {
        $this->ipayout_service = $ipayout_service;
    }

    public function getUser(Request $request)
    {
        return response()->json(
            $this->ipayout_service->getUser(Auth::user()->id)
        );
    }

    public function signUp(Request $request)
    {
        return response()->json(
            $this->ipayout_service->createUser(Auth::user()->id, $request->all())
        );
    }
}
