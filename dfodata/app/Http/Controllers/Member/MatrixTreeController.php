<?php


namespace App\Http\Controllers\Member;

use Commissions\Member\Dashboard;
use Commissions\Member\MatrixTree;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MatrixTreeController extends Controller
{
    protected $matrix_tree;
    protected $dashboard;

    public function __construct(MatrixTree $matrix_tree, Dashboard $dashboard)
    {
        $this->matrix_tree = $matrix_tree;
        $this->dashboard = $dashboard;
    }

    public function getDownline(Request $request, $root_id, $user_id)
    {
        // root_id is the login user

        if($user_id === null) {
            $user_id = $root_id;
        }

        if(!Auth::user()->isSelfOrExistsOnMatrixDownline($user_id)) abort(403);

        $breadcrumb = $this->matrix_tree->getBreadcrumb($root_id, $user_id);

        $downline = $this->matrix_tree->getDownlines($user_id);

        return response()->json(
            compact('downline','breadcrumb')
        );
    }

    public function currentRankDetails(Request $request, $user_id)
    {
        if(!Auth::user()->isSelfOrExistsOnMatrixDownline($user_id)) abort(403);

        return response()->json(
            $this->dashboard->getCurrentRankDetails($user_id)
        );
    }
}