<?php


namespace Commissions\Member;


use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;

class HistoricalCommission
{
    const IS_LOCKED = 1;
    const REPORT_PATH = "csv/member/historical_commission";

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getHistoricalCommission($user_id, $filters)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;

        // default filters
        $draw = intval($filters['draw']);
        $skip = $filters['start'];
        $take = $filters['length'];
        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $frequency = isset($filters['frequency']) ? $filters['frequency'] : null;
        $start_date = isset($filters['start_date']) ? $filters['start_date'] : null;
        $end_date = isset($filters['end_date']) ? $filters['end_date'] : null;
        $commission_type_id = isset($filters['commission_type_id']) ? $filters['commission_type_id'] : null;
        $invoice = isset($filters['invoice']) ? $filters['invoice'] : null;

        if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = $this->getQuery($user_id, $start_date, $end_date, $commission_type_id, $invoice);

        $recordsTotal = $query->count(DB::raw("1"));


        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('s.id', $search)
                    ->orWhere('py.level', $search);
            });
        } elseif (!!$search) {
            $query =
                $query->where(function ($query) use ($search) {
                    $query->where('t.name', 'LIKE', "%{$search}%")
                        ->orWhere('u.site', 'LIKE', "%{$search}%")
                        ->orWhere('u.fname', 'LIKE', "%{$search}%")
                        ->orWhere('u.lname', 'LIKE', "%{$search}%")
                        ->orWhere('s.site', 'LIKE', "%{$search}%")
                        ->orWhere('s.fname', 'LIKE', "%{$search}%")
                        ->orWhere('s.lname', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(u.fname, ' ', u.lname) LIKE ?", ["%{$search}%"])
                        ->orWhere('py.commission_value', 'LIKE', "%{$search}%")
                        ->orWhere('py.percent', 'LIKE', "%{$search}%")
                        ->orWhere('py.amount', 'LIKE', "%{$search}%");
                });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by
        //  order by 1 column
        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir'])->orderBy('py.id');
        } else {
            $query = $query->orderBy('py.id');
        }

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    protected function getQuery($user_id, $start_date, $end_date, $commission_type_id = null, $invoice = null)
    {
        $commission_type_id = $commission_type_id !== null ? $commission_type_id : "all";

        $query = DB::table('cm_commission_payouts AS py')
            ->selectRaw("
                t.name AS commission_type,
                CONCAT(p.start_date, ' to ', p.end_date) AS commission_period,
                py.payee_id,
                CONCAT(s.fname, ' ', s.lname) AS payee,
                py.commission_value AS cv,
                py.percent,
                py.amount,
                py.user_id AS purchaser_id,
                IF(u.id IS NULL, NULL, CONCAT(u.fname, ' ', u.lname)) AS purchaser,
                py.level,
                py.transaction_id,
                tt.invoice
            ")
            ->join('cm_commission_periods AS p', 'p.id', '=', 'py.commission_period_id')
            ->join('cm_commission_types AS t', 't.id', '=', 'p.commission_type_id')
            ->leftJoin('users AS u', 'u.id', '=', 'py.user_id')
            ->leftJoin('users AS s', 's.id', '=', 'py.payee_id')
            ->leftJoin('transactions AS tt', 'tt.id', '=', 'py.transaction_id')
            ->where('p.start_date', $start_date)
            ->where('p.end_date', $end_date)
            ->where('p.is_locked', static::IS_LOCKED);

        if ($user_id !== null) {
            $query = $query->where("py.payee_id", $user_id);
        }

        if ($commission_type_id !== "all") {
            $query = $query->where("t.id", $commission_type_id);
        }

        if (!!$invoice) {
            $query = $query->where("tt.invoice", $invoice);
        }

        return $query;
    }

    public function getDownloadLink($user_id, $start_date, $end_date, $commission_type_id = null, $invoice = null)
    {
        $csv = new CsvReport(static::REPORT_PATH);

        if (!$start_date || !$end_date) {
            $data = [];
        } else {
            $data = $this->getQuery($user_id, $start_date, $end_date, $commission_type_id, $invoice)->get();
        }

        $filename = "historical-commission-$start_date-$end_date-";

        if ($user_id !== null) {
            $filename .= "$user_id-";
        }

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }

    public function getTotalAmount($user_id, $start_date, $end_date, $commission_type_id = null, $invoice = null)
    {
        return $this->getQuery($user_id, $start_date, $end_date, $commission_type_id, $invoice)->sum("py.amount");
    }
}