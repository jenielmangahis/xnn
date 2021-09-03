<?php


namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CommissionAdjustment\SaveRequest;
use App\Http\Controllers\Controller;
use Commissions\Admin\CommissionAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CommissionAdjustmentController extends Controller
{
    protected $commission_adjustment;

    public function __construct(CommissionAdjustment $commission_adjustment)
    {
        $this->commission_adjustment = $commission_adjustment;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->commission_adjustment->getAdjustmentHistory($request->all())
        );
    }

    public function save(SaveRequest $request)
    {
        return response()->json(
            $this->commission_adjustment->save($request->all(), Auth::user()->id)
        );
    }
    
    public function update(SaveRequest $request)
    {
        return response()->json(
            $this->commission_adjustment->update($request->all(), Auth::user()->id)
        );
    }
    
    public function delete(Request $request, $id)
    {
        return response()->json(
            $this->commission_adjustment->delete($id, Auth::user()->id)
        );
    }
}