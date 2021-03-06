<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use Commissions\Admin\SponsorChange;
use Illuminate\Http\Request;
use Exception;

class SponsorChangeController extends Controller
{
    protected $sponsorChangeService;

    public function __construct(SponsorChange $sponsorChange)
    {
        $this->sponsorChangeService = $sponsorChange;
    }

    public function members(Request $request)
    {
        try
        {
            return response()->json(
                $this->sponsorChangeService->members(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function sponsors(Request $request)
    {
        try
        {
            return response()->json(
                $this->sponsorChangeService->sponsors(
                    $request->all()
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function relationship(Request $request)
    {
        try
        {

            return response()->json(
                $this->sponsorChangeService->getRelationship(
                    $request->input('tree_id'),
                    $request->input('member_id'),
                    $request->input('sponsor_id')
                )
            );

        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function change(Request $request)
    {        
        try
        {
            return response()->json(
                $this->sponsorChangeService->changeSponsor(
                    $request->input("tree_id"),
                    $request->input("member_id"),
                    $request->input("sponsor_id"),
                    $request->input("moved_by_id"),
                    $request->input("update_past_orders")
                )
            );
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }

    public function logs(Request $request)
    {
        try
        {
            return response()->json(
                $this->sponsorChangeService->logs(
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
