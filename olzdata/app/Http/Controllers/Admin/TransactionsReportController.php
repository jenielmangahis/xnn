<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Admin\TransactionsReport;
use Illuminate\Support\Facades\Auth;

class TransactionsReportController extends Controller 
{

    protected $adminReportTransactions;

    public function __construct(TransactionsReport $adminReportTransactions) 
    {
        $this->adminReportTransactions = $adminReportTransactions;
    }

    public function getAllTransactions($start_date, $end_date, Request $request)
    {
        return response()->json(
            $this->adminReportTransactions->getTransactionsDateRange($start_date, $end_date, $request->input('status'), Auth::user()->id)
        );
    }

    public function getAllBreakDown($start_date, $end_date, Request $request)
    {
        return response()->json(
            $this->adminReportTransactions->getTotalAmountsPerTransactionTypeV2($start_date, $end_date)
        );
    }

    public function getReport($start_date, $end_date, Request $request)
    {
        return response()->json($this->adminReportTransactions->getReportCSV($start_date, $end_date, $request->input('status'), Auth::user()->id));
    }

    public function getLineItem($start_date, $end_date, Request $request)
    {
        return response()->json($this->adminReportTransactions->getLineItemReport($start_date, $end_date, $request->input('status'), Auth::user()->id));
    }
    
    public function getTransactionLevel($start_date, $end_date, Request $request)
    {
        return response()->json($this->adminReportTransactions->getTransactionLevelReport($start_date, $end_date, $request->input('status'), Auth::user()->id));
    }

    public function getDownload($file_name)
    {
        $file = public_path("file/".$file_name);
        $headers = array(
            'Content-Type: csv'
        );
        if ( file_exists( $file ) ) {
            // Send Download
            return \Response::download( $file, $file_name, $headers, Auth::user()->id );
        } else {
            // Error
            exit( 'Requested file does not exist on our server!' );
        }
    }
}