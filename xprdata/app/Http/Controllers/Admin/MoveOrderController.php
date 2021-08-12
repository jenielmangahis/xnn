<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Commissions\Admin\MoveOrder;
use Illuminate\Support\Facades\Auth;
use Exception;

class MoveOrderController extends Controller
{
    protected $moveOrderService;

    public function __construct(MoveOrder $moveOrder)
    {
        $this->moveOrderService = $moveOrder;
    }

    public function logs(Request $request)
    {
        try
        {
            return response()->json(
                $this->moveOrderService->logs(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }
    
    public function change(Request $request, $id)
    {
        try
        {
            return response()->json(
                $this->moveOrderService->change($id, $request->all())
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }
    
}
