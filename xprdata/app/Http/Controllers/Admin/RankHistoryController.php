<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Commissions\Admin\RankHistory;
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
            $this->rank_history->getEnrollment($request->all())
        );
    }

    public function highest(Request $request)
    {
        return response()->json(
            $this->rank_history->getHighest($request->all())
        );
    }

    public function downloadEnrollment(Request $request)
    {
        sleep(2); // test loading
        return response()->json([
            'link' => $this->rank_history->getEnrollmentDownloadLink(
                $request->input('start_date'),
                $request->input('rank_id')
            )
        ]);
    }

    public function downloadHighest(Request $request)
    {
        sleep(2); // test loading
        return response()->json([
            'link' => $this->rank_history->getHighestDownloadLink(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('rank_id'),
                $request->input('is_all')
            )
        ]);
    }

}