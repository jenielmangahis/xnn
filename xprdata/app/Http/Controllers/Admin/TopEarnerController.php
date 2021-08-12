<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Commissions\Admin\TopEarner;
use Illuminate\Support\Facades\Auth;

class TopEarnerController extends Controller 
{
    protected $top_earner;

    public function __construct(TopEarner $top_earner) 
    {
        $this->top_earner = $top_earner;
    }

    public function topEarner(Request $request)
    {
        return response()->json(
            $this->top_earner->getTopEarners($request->all(), Auth::user()->id)
        );
    }
}
