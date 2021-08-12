<?php


namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Commissions\Member\PlacementTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlacementTreeController extends Controller
{
    protected $placement_tree;

    public function __construct(PlacementTree $placement_tree)
    {
        $this->placement_tree = $placement_tree;
    }

    public function parent($id)
    {
        if(!Auth::user()->isSelfOrExistsOnPlacementDownline($id)) abort(403);

        return response()->json(
            $this->placement_tree->getParentDetails($id)
        );
    }

    public function children($id, $page_no)
    {
        if(!Auth::user()->isSelfOrExistsOnPlacementDownline($id)) abort(403);

        return response()->json(
            $this->placement_tree->getChildrenPaginate($id, $page_no)
        );
    }

    public function orderHistory(Request $request)
    {
        $user_id = $request->input('user_id');

        if(!Auth::user()->isSelfOrExistsOnPlacementDownline($user_id)) abort(403);

        return response()->json(
            $this->placement_tree->getOrderHistory($request->all(), $user_id)
        );
    }

    public function unplacedMembers(Request $request)
    {
        return response()->json(
            $this->placement_tree->getUnplacedMembers(Auth::user()->id)
        );
    }

    public function placeMember(Request $request)
    {
        $user_id = $request->input("user_id");
        $sponsor_id = $request->input("new_sponsor_id");

        if(!Auth::user()->isSelfOrExistsOnPlacementDownline($user_id) || !Auth::user()->isSelfOrExistsOnPlacementDownline($sponsor_id)) abort(403);

        return response()->json(
            $this->placement_tree->placeMember($user_id, $sponsor_id, Auth::user()->id)
        );
    }

}