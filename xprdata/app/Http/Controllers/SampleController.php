<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SampleController extends Controller
{
    public function index()
    {

    }

    public function pdo()
    {
        $db = DB::connection()->getPdo();

        $sql = "SELECT DATABASE()";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $db_name = $stmt->fetchColumn();

        return response()->json(['db_name' => $db_name]);
    }

    public function validation(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);
        return response()->json($request->all());
    }

    public function exception()
    {
        throw new \Exception("Test Throw");
    }

    public function config()
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers = config('commission.member-types.customers');

        return response()->json([
            'affiliates' => $affiliates,
            'customers' => $customers,
        ]);
    }

    public function datatables(Request $request)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;
        $filters = $request->all();

        $user_id = Auth::user()->id;

        return response()->json(compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'filters', 'user_id'));
    }
}
