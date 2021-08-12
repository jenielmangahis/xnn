<?php


namespace App\Http\Controllers\Admin;


use App\Http\Requests\Admin\MinimumRank\SaveRequest;
use Commissions\Admin\MinimumRank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MinimumRankController
{
    protected $minimum_rank;

    public function __construct(MinimumRank $minimum_rank)
    {
        $this->minimum_rank = $minimum_rank;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->minimum_rank->getMinimumRanks($request->all())
        );
    }

    public function save(SaveRequest $request)
    {

        return response()->json(
            $this->minimum_rank->save($request->all(), Auth::user()->id)
        );
    }

    public function delete(Request $request, $user_id)
    {
        return response()->json(
            $this->minimum_rank->delete($user_id, Auth::user()->id)
        );
    }

    public function show(Request $request, $user_id)
    {
        return response()->json(
            $this->minimum_rank->getMinimumRank($user_id)
        );
    }
}