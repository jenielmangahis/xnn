<?php


namespace Commissions\Member;

use Illuminate\Support\Facades\DB;
use PDF;

class ReceiptDetail
{

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }
    
    public function getAllReceipts($filters, $user_id)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date = isset($filters['end_date']) ? $filters['end_date'] : null;

        if (!$start_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date', 'end_date');
        }

        $query = $this->getAllReceiptsQuery($user_id, $start_date, $end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('p.receipt_num', 'LIKE', "%$search%")
                    ->orWhere('p.bank_transaction_code', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('p.actual_date', 'LIKE', "%{$search}%")
                    ->orWhere('p.period_of_reference', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("p.id", "DESC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date', 'end_date', 'user_id');
    }
    
    protected function getAllReceiptsQuery($user_id, $start_date, $end_date)
    {

        $query =
            DB::table('cm_payments AS p')
            ->join("users AS u", "u.id", "=", "p.user_id")
            ->selectRaw("
                p.id,
                p.created_date AS actual_date,
                p.receipt_num AS receipt_number,
                p.bank_transaction_code AS bank_reference,
                p.period_of_reference AS month_reference
            ")
         //   ->whereRaw("p.actual_date BETWEEN $start_date AND $end_date")
            ->whereRaw("DATE(p.created_at) BETWEEN '$start_date' AND '$end_date'")
            ->where('u.id', '=', $user_id)
            ->where('p.is_processed', '=', 1);

		//just a test
		/*
		$query =
            DB::table('cm_payments AS p')
            ->join("users AS u", "u.id", "=", "p.user_id")
            ->selectRaw("
                p.id,
                IFNULL(p.actual_date, '2021-01-31') AS actual_date,
                IFNULL(p.receipt_num, '1234567') AS receipt_number,
                IFNULL(p.bank_transaction_code, '1234567') AS bank_reference,
				IFNULL(p.period_of_reference, '2021-01-01 - 2021-01-31') AS month_reference
            ")
            //->whereRaw("p.actual_date BETWEEN $start_date AND $end_date")
            //->whereRaw("DATE(p.created_at) BETWEEN $start_date AND $end_date")
            ->where('u.id', '=', $user_id);
		*/

        return $query;
    }

    protected function getReceiptDetailsQuery($receipt_id)
    {
        $details =
            DB::table('cm_payments AS p')
            ->join("users AS u", "u.id", "=", "p.user_id")
            ->selectRaw("
                p.receipt_num,
                u.id AS associate_id,
                u.`fname` AS first_name,
                u.`lname` AS last_name,
                CONCAT(u.`address`, ' ', u.`city`, ', ', u.`state`, ', ', u.`zip`, ', ', u.`country`) AS address,
                u.`piva`,
                p.`gross_weekly_immediate_earnings`,
                p.`gross_monthly_earnings_true_up`,
                p.`gross_monthly_residual_personal_energy_account`,
                p.`gross_unilevel_residual`,
                p.`gross_generation_residual`,
                p.`other_income`,
                p.`total_gross`,
                p.taxes_ritenuta_irpef,
                p.taxes_vat,
                p.taxes_trattenuta_previd,
                p.techonology_fee_to_subtract,
                p.total_net_amount,
                DATE_FORMAT(p.created_date, '%d/%m/%Y') AS actual_date,
                p.bank_transaction_code,
                p.`iban`,
                p.cf
            ")
            ->where('p.id', '=', $receipt_id)
            ->first();

        return $details;
    }

    public function downloadPDF($receipt_id) 
    {
        $receipt = $this->getReceiptDetailsQuery($receipt_id);

		$pdf = PDF::loadView('receipt', ['receipt' => $receipt]);

		if(!is_dir(storage_path("app/public/pdf/member/"))) {
            mkdir(storage_path("app/public/pdf/member/"), 0777, true);
        }

		// If you want to store the generated pdf to the server then you can use the store function
		$pdf->save(storage_path()."/app/public/pdf/member/receipt-$receipt_id.pdf");

		// Finally, you can download the file using download function
		//return $pdf->download('receipt.pdf');
        return asset("storage/pdf/member/receipt-$receipt_id.pdf");
    }

}