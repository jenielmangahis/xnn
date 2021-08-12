<?php


namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Commissions\Member\RankHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RankHistoryController extends Controller
{

    protected $rank_history;

    public function __construct(RankHistory $rank_history)
    {
        $this->rank_history = $rank_history;
    }

    public function enrollment(Request $request)
    {
        return response()->json(
            $this->rank_history->getEnrollment($request->all(), Auth::user()->id)
        );
    }

    public function personal(Request $request)
    {
        return response()->json(
            $this->rank_history->getPersonal($request->all(), Auth::user()->id)
        );
    }

    public function highest(Request $request)
    {
        return response()->json(
            $this->rank_history->getHighest($request->all(), Auth::user()->id)
        );
    }
}