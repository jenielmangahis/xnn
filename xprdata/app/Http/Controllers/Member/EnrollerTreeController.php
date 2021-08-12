<?php


namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Commissions\Member\EnrollerTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollerTreeController extends Controller
{
    protected $enroller_tree;

    public function __construct(EnrollerTree $enroller_tree)
    {
        $this->enroller_tree = $enroller_tree;
    }

    public function parent($id)
    {
        if(!Auth::user()->isSelfOrExistsOnEnrollerDownline($id)) abort(403);

        return response()->json(
            $this->enroller_tree->getParentDetails($id)
        );
    }

    public function children($id, $page_no)
    {
        if(!Auth::user()->isSelfOrExistsOnEnrollerDownline($id)) abort(403);

        return response()->json(
            $this->enroller_tree->getChildrenPaginate($id, $page_no)
        );
    }

    public function orderHistory(Request $request)
    {
        $user_id = $request->input('user_id');

         if(!Auth::user()->isSelfOrExistsOnEnrollerDownline($user_id)) abort(403);

        return response()->json(
            $this->enroller_tree->getOrderHistory($request->all(), $user_id)
        );
    }

}