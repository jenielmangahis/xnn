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

    public function getNewEndorserCount(Request $request)
    {
        return response()->json([
            'count' => $this->dashboard->getNewEndorserCount($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getNewEndorserWithProductSubscriptionCount(Request $request)
    {
        return response()->json([
            'count' => $this->dashboard->getNewEndorserWithProductSubscriptionCount($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getTransformationPackTotalSales(Request $request)
    {
        return response()->json([
            'total_sales' =>  $this->dashboard->getTransformationPackTotalSales($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getElitePackTotalSales(Request $request)
    {
        return response()->json([
            'total_sales' =>  $this->dashboard->getElitePackTotalSales($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getFamilyElitePackTotalSales(Request $request)
    {
        return response()->json([
            'total_sales' => $this->dashboard->getFamilyElitePackTotalSales($request->input('start_date'), $request->input('end_date'))
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
            $this->dashboard->getNewCustomers($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getNewCustomersWithProductSubscription(Request $request)
    {
        return response()->json(
            $this->dashboard->getNewCustomersWithProductSubscription($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getNewEndorsers(Request $request)
    {
        return response()->json(
            $this->dashboard->getNewEndorsers($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getNewEndorsersWithProductSubscription(Request $request)
    {
        return response()->json(
            $this->dashboard->getNewEndorsersWithProductSubscription($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getTransformationPackSales(Request $request)
    {
        return response()->json(
            $this->dashboard->getTransformationPackSales($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getElitePackSales(Request $request)
    {
        return response()->json(
            $this->dashboard->getElitePackSales($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getFamilyElitePackSales(Request $request)
    {
        return response()->json(
            $this->dashboard->getFamilyElitePackSales($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getEndorsersIncludingFirstPurchase(Request $request, $user_id)
    {
        return response()->json(
            $this->dashboard->getEndorsersIncludingFirstPurchase($user_id, $request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getCustomerTransformationPackTotalSales(Request $request)
    {
        return response()->json([
            'total_sales' =>  $this->dashboard->getCustomerTransformationPackTotalSales($request->input('start_date'), $request->input('end_date'))
        ]);
    }

    public function getCustomerTransformationPackSales(Request $request)
    {
        return response()->json(
            $this->dashboard->getCustomerTransformationPackSales($request->input('start_date'), $request->input('end_date'))
        );
    }

    public function getDownloadLink(Request $request)
    {
        return response()->json([
            'link' => $this->dashboard->getDownloadLink($request->input('report_type'), $request->input('start_date'), $request->input('end_date'), $request->input('user_id'))
        ]);
    }
}