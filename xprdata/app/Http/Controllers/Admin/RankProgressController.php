<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Commissions\Admin\RankProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RankProgressController extends Controller
{
    protected $rank_progress;

    public function __construct(RankProgress $rank_progress)
    {
        $this->rank_progress = $rank_progress;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->rank_progress->getProgress($request->all())
        );
    }

}