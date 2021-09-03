<?php


namespace App\Http\Controllers\Member;

use Commissions\Member\BinaryTree;
use Commissions\Member\Dashboard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BinaryTreeController extends Controller
{
    protected $binaryTree;
    protected $dashboard;

    public function __construct(BinaryTree $binaryTree, Dashboard $dashboard)
    {
        $this->binaryTree = $binaryTree;
        $this->dashboard = $dashboard;
    }

    public function getDownline(Request $request, $root_id, $user_id)
    {
        // root_id is the login user

        if($user_id === null) {
            $user_id = $root_id;
        }

        if(!Auth::user()->isSelfOrExistsOnBinaryDownline($user_id)) abort(403);

        $breadcrumb = $this->binaryTree->getBreadcrumb($root_id, $user_id);

        $downline = $this->binaryTree->getDownlines($user_id);

        return response()->json(
            compact('downline','breadcrumb')
        );
    }

    public function getUserDetails(Request $request)
    {
        return response()->json(
            $this->binaryTree->getUserDetails(Auth::user()->id)
        );
    }

    public function updatePlacementPreference(Request $request)
    {
        return response()->json(
            $this->binaryTree->setPlacementPreference(Auth::user()->id, $request->input('placement_preference'))
        );
    }
}