<?php


namespace Commissions\Member;

use Illuminate\Support\Facades\DB;
use PDF;

class TechFeeDetail
{

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getAllReceipts($filters)
    {
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];
        $user_id = $filters['memberid'];

        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date = isset($filters['end_date']) ? $filters['end_date'] : null;


        $query = $this->getAllTechFee($user_id, $start_date, $end_date);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";
/**
        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search, $level) {
                $query->where('p.receipt_num', $search)
                    ->orWhere('p.bank_transaction_code', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('p.actual_date', 'LIKE', "%{$search}%")
                    ->orWhere('p.period_of_reference', 'LIKE', "%{$search}%");
            });
        }
*/
        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("p.receipt_id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date', 'end_date', 'user_id');
    }

    public function download($type, $id)
    {
        $receipt = $this->view($type, $id);

		$pdf = PDF::loadView('techfee', ['receipt' => $receipt]);

		if(!is_dir(storage_path("app/public/pdf/member/"))) {
            mkdir(storage_path("app/public/pdf/member/"), 0777, true);
        }


		$pdf->save(storage_path()."/app/public/pdf/member/techfee-$id.pdf");


        return asset("storage/pdf/member/techfee-$id.pdf");
    }

    public function getAllTechFee($user_id=null, $start_date = null, $end_date = null)
    {
        $r = DB::table(
                DB::raw("
                    (
                    SELECT id AS receipt_id
                        , user_id
                        , created_at AS tdate
                        , receipt_num AS invoice
                        , 'commissions' AS payment_type 
                        , IF(status = 'SUCCESS', 'Paid', '') as status 
                        , DATE_FORMAT(technology_fee_description, '%M %Y') as pay_desc
                        , amount
                    FROM cm_payments WHERE techonology_fee_to_subtract > 0
                    
                    UNION 
                    
                    SELECT id AS receipt_id
                        , userid
                        , transactiondate AS tdate
                        , invoice
                        , 'billing' AS payment_type
                        , IF(status = 'Approved', 'Paid', '') as status  
                        , DATE_FORMAT(transactiondate, '%M %Y') as pay_desc                
                        , amount          
                    FROM transactions WHERE `type` = 'sub' AND `status` = 'Approved'
                    ) AS p
                ")
            )->join('users as u', 'u.id', '=', 'p.user_id')
            ->selectRaw('p.*')
        ;

        if($user_id !== null)
        {
            $r->where('u.id', $user_id);
        }


        if($start_date !== null && $end_date !== null)
        {
            $r->whereBetween('p.tdate', [$start_date, $end_date]);
        }

        return $r;
    }


    public function view($receipt_type, $id)
    {
        $q = DB::table('users as u')
            ->selectRaw(
                "
                    CONCAT_WS(' ', u.fname, u.lname) as user_name
                    , u.cf
                    , CONCAT_WS(' ', u.address, u.address2) as user_address
                    , CONCAT_WS(' ', u.zip, u.state, u.country) as user_address2
                    "
            );

        if($receipt_type == 'commissions')
        {
            $q = $q->selectRaw("c.receipt_num as invoice
            , DATE_FORMAT(c.created_date, '%d/%m/%Y') as rdate
            , DATE_FORMAT(c.technology_fee_description, '%M %Y') as pay_desc
            , COALESCE(c.amount, 0) AS amount
            , COALESCE(c.amount * 0.22, 0) AS tax
            , COALESCE(c.amount + (c.amount * 0.22), 0) AS total
            ")
                ->join('cm_payments as c', 'u.id', '=', 'c.user_id')
                ->where('c.id', $id);
        }
        else
        {
            $q = $q->selectRaw("c.invoice as invoice
            , DATE_FORMAT(c.transactiondate, '%d/%m/%Y') as rdate
            , DATE_FORMAT(c.transactiondate, '%M %Y') as pay_desc
            , COALESCE(c.amount, 0) AS amount
            , COALESCE(c.amount * 0.22, 0) AS tax
            , COALESCE(c.amount + (c.amount * 0.22), 0) AS total
            ")
                ->join('transactions as c', 'u.id', '=', 'c.userid')
                ->where('c.id', $id);
        }

        return $q->first();
    }
}