<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Commissions\Admin\IncentiveTool;
use App\Http\Requests\Admin\IncentiveTool\SaveRequest;

class IncentiveToolController extends Controller
{
    protected $incentive_tool;

    public function __construct(IncentiveTool $incentiveTool)
    {
        $this->incentive_tool = $incentiveTool;
    }

    public function running(Request $request)
    {
        return response()->json(
            $this->incentive_tool->getRunningIncentive($request->all())
        );
    }

    public function closed(Request $request)
    {
        return response()->json(
            $this->incentive_tool->getClosedIncentive($request->all())
        );
    }

    public function arbitrary(Request $request)
    {
        return response()->json(
            $this->incentive_tool->getArbitraryBonus($request->all())
        );
    }

    public function view(Request $request)
    {
        $id = $request->input('id');
        $is_active = $request->input('is_active');
        $is_locked = $request->input('is_locked');
        return response()->json(
            $this->incentive_tool->getAllRepresentativeByIncentive($request->all(), $id, $is_active, $is_locked)
        );
    }

    public function getRanks(Request $request)
    {
        return response()->json(
            $this->incentive_tool->getRanks()
        );  
    }

    public function getRepresentatives(Request $request)
    {
        return response()->json(
            $this->incentive_tool->getRepresentatives($request->all())
        );  
    }

    public function getOpenIncentives(Request $request)
    {
        return response()->json(
            $this->incentive_tool->getOpenIncentives()
        );  
    }

    public function getIncentiveSettings(Request $request, $settings_id)
    {
        return response()->json(
            $this->incentive_tool->getIncentiveSettings($settings_id)
        );  
    }

    public function download(Request $request, $settings_id)
    {
        return response()->json([
            'link' => $this->incentive_tool->download($settings_id)
        ]); 
    }

    public function store(SaveRequest $request)
    {
        return response()->json(
            $this->incentive_tool->saveIncentive($request->all())
        );
    }

    public function update(Request $request)
    {
        return response()->json(
            $this->incentive_tool->updateIncentive($request->all())
        );
    }

    public function delete(Request $request, $settings_id)
    {
        return response()->json(
            $this->incentive_tool->deleteIncentive($settings_id)
        );
    }

    public function hide(Request $request, $settings_id)
    {
        return response()->json(
            $this->incentive_tool->hideIncentive($settings_id)
        );
    }

    public function deleteArbitrary(Request $request, $id)
    {
        return response()->json(
            $this->incentive_tool->deleteArbitraryPoints($id)
        );
    }

    public function addArbitrary(Request $request)
    {
        return response()->json(
            $this->incentive_tool->addArbitraryPoints($request->all())
        );
    }

}
