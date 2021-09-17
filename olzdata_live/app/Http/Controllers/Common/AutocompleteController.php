<?php


namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Commissions\Common\Autocomplete;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutocompleteController extends Controller
{
    protected $autocomplete;

    public function __construct(Autocomplete $autocomplete)
    {
        $this->autocomplete = $autocomplete;
    }

    public function enrollerDownline(Request $request)
    {
        return response()->json(
            $this->autocomplete->getEnrollerDownline(Auth::user()->id, $request->input("term"), +$request->input("page"))
        );
    }

    public function placementDownline(Request $request)
    {
        return response()->json(
            $this->autocomplete->getPlacementDownline(Auth::user()->id, $request->input("term"), +$request->input("page"))
        );
    }

    public function matrixDownline(Request $request)
    {
        return response()->json(
            $this->autocomplete->getMatrixDownline(Auth::user()->id, $request->input("term"), +$request->input("page"))
        );
    }

    public function members(Request $request)
    {
        return response()->json(
            $this->autocomplete->getMembers($request->input("term"), +$request->input("page"))
        );
    }

    public function affiliates(Request $request)
    {
        return response()->json(
            $this->autocomplete->getAffiliates($request->input("term"), +$request->input("page"))
        );
    }
    
    public function enrollerCustomerDownline(Request $request)
    {
        return response()->json(
            $this->autocomplete->getEnrollerCustomerDownline(Auth::user()->id, $request->input("term"), +$request->input("page"))
        );
    }
}