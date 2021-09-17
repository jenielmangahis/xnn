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

    public function parent($id,$start_date)
    {
        if(!Auth::user()->isSelfOrExistsOnEnrollerDownline($id)) abort(403);

        $current_date = date("Y-m-d");
        $start_date = $start_date > $current_date ? $current_date : $start_date;
        return response()->json(
            $this->enroller_tree->getParentDetails($id,$start_date)
        );
    }

    public function children($id, $page_no, $start_date)
    {
        if(!Auth::user()->isSelfOrExistsOnEnrollerDownline($id)) abort(403);

        $current_date = date("Y-m-d");
        $start_date = $start_date > $current_date ? $current_date : $start_date;
        return response()->json(
            $this->enroller_tree->getChildrenPaginate($id, $start_date, $page_no)
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

    public function getDownlines(Request $request)
    {
        try
        {
            $filter = $request->input('f');
            $query = $request->input('q');
            $memberid = $request->input('m');

            // throw new \ErrorException($filter . ' ' . $query . ' ' . $memberid);

            $members = $this->enroller_tree->getUserDownlines($filter, $query, $memberid);

            $results = array();
            foreach ($members as $key => $member) {
                $results[] = array(
                    "value" => $member['fname'] . ' ' . $member['lname'],
                    "site" => $member['site'],
                    "id" => $member['id'],
                    "display" => '#' . $member['id'] . ': ' . $member['fname'] . ' ' . $member['lname'],
                );
            }

            return response()->json($results);
        } catch (Exception $ex) {
            return response()->json(['error' => ['message' => $ex->getMessage()]], 400);
        }
    }

    public function wishlist(Request $request)
    {
        $user_id = $request->input('user_id');

         if(!Auth::user()->isSelfOrExistsOnEnrollerDownline($user_id)) abort(403);

        return response()->json(
            $this->enroller_tree->getWishlist($request->all(), $user_id)
        );
    }

}