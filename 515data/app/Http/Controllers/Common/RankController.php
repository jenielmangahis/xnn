<?php


namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Rank;
use Illuminate\Http\Request;

class RankController extends Controller
{
    public function index(Request $request)
    {
        $ranks = Rank::query();

        if($request->input('excludes_ids') !== null) {
            $ids = explode(",", $request->input('excludes_ids'));
            $ranks->whereNotIn('id', $ids);
        }

        return $ranks->orderBy('id', 'desc')->get();
    }
}