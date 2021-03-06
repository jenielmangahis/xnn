<?php


namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Commissions\Member\Payeer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayeerController extends Controller
{
    protected $payeer_service;

    public function __construct(Payeer $payeer_service)
    {
        $this->payeer_service = $payeer_service;
    }

    public function getUser(Request $request)
    {
        return response()->json(
            $this->payeer_service->getUser(Auth::user()->id)
        );
    }

    public function createUser(Request $request)
    {
        return response()->json(
            $this->payeer_service->createUser(Auth::user()->id, $request->all())
        );
    }

    public function updateUser(Request $request)
    {
        return response()->json(
            $this->payeer_service->updateUser(Auth::user()->id, $request->all())
        );
    }
}