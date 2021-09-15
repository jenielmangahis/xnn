<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use Commissions\Admin\Clawback;
use Exception;

class ClawbackController extends Controller
{
    protected $clawbackService;

    public function __construct(Clawback $clawback)
    {
        $this->clawbackService = $clawback;
    }

    public function clawbacks(Request $request)
    {
        try
        {
            return response()->json(
                $this->clawbackService->datatables(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function products($transaction_id)
    {
        try
        {
            return response()->json($this->clawbackService->orderProducts($transaction_id));
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function refundOrder(Request $request)
    {
        try
        {
            return response()->json(
                $this->clawbackService->refundOrder(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function refundProduct(Request $request)
    {
        try
        {
            return response()->json(
                $this->clawbackService->refundOrderProducts(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function getPEA(Request $request)
    {
        try
        {
            return response()->json(
                $this->clawbackService->getPEA(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function getPayouts(Request $request)
    {
        try
        {
            return response()->json(
                $this->clawbackService->getPayouts(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }
}
