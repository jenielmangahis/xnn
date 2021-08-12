<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Member\Hyperwallet;
use Illuminate\Support\Facades\Auth;

class HyperwalletController extends Controller
{
    protected $hyperwallet_service;

    public function __construct(Hyperwallet $hyperwallet_service)
    {
        $this->hyperwallet_service = $hyperwallet_service;
    }

    public function getUser(Request $request)
    {
        return response()->json(
            $this->hyperwallet_service->getUser(Auth::user()->id)
        );
    }

    public function signUp(Request $request)
    {
        return response()->json(
            $this->hyperwallet_service->createUser(Auth::user()->id, $request->all())
        );
    }
}
