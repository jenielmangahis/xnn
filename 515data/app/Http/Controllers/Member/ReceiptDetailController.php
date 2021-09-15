<?php

namespace App\Http\Controllers\Member;

use Commissions\Member\ReceiptDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReceiptDetailController extends Controller
{
    protected $receipt_detail_service;

    public function __construct(ReceiptDetail $receipt_detail_service)
    {
        $this->receipt_detail_service = $receipt_detail_service;
    }

    public function getAllReceipts(Request $request)
    {
        return response()->json(
            $this->receipt_detail_service->getAllReceipts($request->all(), Auth::user()->id)
        );
    }

    public function downloadPDF($id)
    {
		return $this->receipt_detail_service->downloadPDF($id);
    }
}
