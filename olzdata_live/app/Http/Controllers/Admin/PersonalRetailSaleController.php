<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Commissions\Admin\PersonalRetailSale;;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalRetailSaleController extends Controller
{

    protected $personal_retail_sale;

    public function __construct(PersonalRetailSale $personal_retail_sale)
    {
        $this->personal_retail_sale = $personal_retail_sale;
    }

    public function enrollment(Request $request)
    {
        return response()->json(
            $this->personal_retail_sale->getEnrollment($request->all())
        );
    }

    public function downloadPersonalRetail(Request $request)
    {
        sleep(2);
        return response()->json([
            'link' => $this->personal_retail_sale->getPersonalRetailDownloadLink(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('memberId'),
                $request->input('prs_500_above')
            )
        ]);
    }
}