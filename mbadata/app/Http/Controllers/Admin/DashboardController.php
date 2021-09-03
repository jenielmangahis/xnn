<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Commissions\Admin\Dashboard;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    protected $dashboard;
    public function __construct()
    {
        $this->dashboard = new Dashboard();
    }

    public function getNewCustomerCount(Request $request)
    {
        return response()->json([
            'count' => $this->dashboard->getNewCustomerCount($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getNewCustomerWithProductSubscriptionCount(Request $request)
    {
        return response()->json([
            'count' => $this->dashboard->getNewCustomerWithProductSubscriptionCount($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getNewIBOCount(Request $request)
    {
        return response()->json([
            'count' => $this->dashboard->getNewIBOCount($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getNewIBOWithProductSubscriptionCount(Request $request)
    {
        return response()->json([
            'count' => $this->dashboard->getNewIBOWithProductSubscriptionCount($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getGoldPackageTotalSales(Request $request)
    {
        return response()->json([
            'total_sales' =>  $this->dashboard->getGoldPackageTotalSales($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getIBOTotalSales(Request $request)
    {
        return response()->json([
            'total_sales' =>  $this->dashboard->getIBOTotalSales($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getAverageReorder(Request $request)
    {
        return response()->json([
            'average' => $this->dashboard->getAverageReorder($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getTopEndorsers(Request $request)
    {
        return response()->json(
            $this->dashboard->getTopEndorsers($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getViralIndex(Request $request)
    {
        return response()->json(
            $this->dashboard->getViralIndex($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getNewCustomers(Request $request)
    {
        return response()->json(
            $this->dashboard->getNewCustomers($request->all())
        );
    }

    public function getNewCustomersWithProductSubscription(Request $request)
    {
        return response()->json(
            $this->dashboard->getNewCustomersWithProductSubscription($request->all())
        );
    }

    public function getNewIBO(Request $request)
    {
        return response()->json(
            $this->dashboard->getNewIBO($request->all())
        );
    }

    public function getNewIBOWithProductSubscription(Request $request)
    {
        return response()->json(
            $this->dashboard->getNewIBOWithProductSubscription($request->all())
        );
    }

    public function getGoldPackageSales(Request $request)
    {
        return response()->json(
            $this->dashboard->getGoldPackageSales($request->all())
        );
    }

    public function getIBOSales(Request $request)
    {
        return response()->json(
            $this->dashboard->getIBOSales($request->all())
        );
    }

    public function getPlatinumPackageTotalSales(Request $request)
    {
        return response()->json([
            'total_sales' =>  $this->dashboard->getPlatinumPackageTotalSales($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getPlatinumPackageSales(Request $request)
    {
        return response()->json(
            $this->dashboard->getPlatinumPackageSales($request->all())
        );
    }

    public function getDownloadLink(Request $request)
    {
        return response()->json([
            'link' => $this->dashboard->getDownloadLink($request->input('report_type'), $request->input('start_date'), $request->input('end_date'), $request->input('user_id'))
        ]);
    }
}