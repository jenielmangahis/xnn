<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Commissions\Admin\UplineReport;
use Illuminate\Support\Facades\Auth;

class UplineReportController extends Controller 
{
    protected $upline;

    public function __construct(UplineReport $upline) 
    {
        $this->upline = $upline;
    }

    public function view(Request $request, $member_id)
    {
        return response()->json (
            $this->upline->getUplines($member_id, $request->input("tree_type"))
        );
    }
}
