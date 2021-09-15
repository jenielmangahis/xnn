<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Member\TechFeeDetail;
use Illuminate\Support\Facades\Auth;


class TechFeeReceiptController extends Controller
{
    protected $tech_fee_detail_service;

    public function __construct(TechFeeDetail $receipt_detail_service)
    {
        $this->receipt_detail_service = $receipt_detail_service;
    }

    public function index(Request $request)
    {
        return $this->receipt_detail_service->getAllReceipts($request);

    }

    public function view(Request $request)
    {
        $data = $this->receipt_detail_service->view($request->receipt_type, $request->receipt_id);
        return view('techfee', ['receipt' => $data]);
    }

    public function download(Request $request)
    {
        return $this->receipt_detail_service->download($request->receipt_type, $request->receipt_id);
    }
    //
}
