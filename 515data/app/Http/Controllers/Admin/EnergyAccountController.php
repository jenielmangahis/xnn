<?php


namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;
use Commissions\Admin\EnergyAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnergyAccountController extends Controller
{
    protected $energyAccount;

    public function __construct(EnergyAccount $energyAccount)
    {
        $this->energyAccount = $energyAccount;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->energyAccount->getEnergyAccounts($request->all(), Auth::user()->id)
        );
    }

    public function status(Request $request)
    {
        return response()->json(
            $this->energyAccount->getEnergyAccountStatus($request->all(), Auth::user()->id)
        );
    }

    public function statusCount(Request $request)
    {
        return response()->json(
            $this->energyAccount->getStatusCount($request->all())
        );
    }

    public function showStatus($referenceId)
    {
        return response()->json(
            $this->energyAccount->showStatus($referenceId)
        );
    }
}