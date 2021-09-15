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
        $downline_id = isset($filters['downline_id']) ? $filters['downline_id'] : null;

        if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $query = $this->getQuery($user_id, $start_date, $end_date,$downline_id, $commission_type_id);

        $recordsTotal = $query->count(DB::raw("1"));


        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('t_payee.id', $search)
                    ->orWhere('t_associate.id', $search)
                    ->orWhere('py.level', $search)
                    ->orWhere('cm_energy_accounts.reference_id', 'LIKE', "%{$search}%");
            });
        } elseif (!!$search) {
            $query =
                $query->where(function ($query) use ($search) {
                    $query->where('t.name', 'LIKE', "%{$search}%")
                        ->orWhere('t_payee.site', 'LIKE', "%{$search}%")
                        ->orWhere('t_payee.fname', 'LIKE', "%{$search}%")
                        ->orWhere('t_payee.lname', 'LIKE', "%{$search}%")
                        ->orWhere('t_associate.site', 'LIKE', "%{$search}%")
                        ->orWhere('t_associate.fname', 'LIKE', "%{$search}%")
                        ->orWhere('t_associate.lname', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(t_payee.fname, ' ', t_payee.lname) LIKE ?", ["%{$search}%"])
                        ->orWhere('py.commission_value', 'LIKE', "%{$search}%")
                        ->orWhere('py.percent', 'LIKE', "%{$search}%")
                        ->orWhere('py.amount', 'LIKE', "%{$search}%")
                        ;
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

    protected function getQuery($user_id, $start_date, $end_date, $downline_id, $commission_type_id = null)
    {
        $commission_type_id = $commission_type_id !== null ? $commission_type_id : "all";

        $query = DB::table('cm_commission_payouts AS py')
            ->selectRaw("
                t.name AS commission_type,
                CONCAT(p.start_date, ' to ', p.end_date) AS commission_period,
                py.payee_id,
                CONCAT(t_payee.fname, ' ', t_payee.lname) AS payee,
                COALESCE(CONCAT(t_customer.fname,' ', LEFT(t_customer.lname, 1)), LEFT(t_customer.business, 5)) AS purchaser,
                py.sponsor_id,
                CONCAT(t_associate.fname, ' ',t_associate.lname) AS sponsor_name,
                CONCAT(REPEAT('*', CHAR_LENGTH(cm_energy_accounts.reference_id) - 5), SUBSTR(cm_energy_accounts.reference_id, -5)) AS reference_id,
                py.amount AS amount_earned,
                py.level,
                cm_energy_account_types.type AS account_type,
                r.name AS current_rank
            ")
            ->join('cm_commission_periods AS p', 'p.id', '=', 'py.commission_period_id')
            ->join('cm_commission_types AS t', 't.id', '=', 'p.commission_type_id')
            ->leftJoin('cm_energy_accounts', function($join) {
                $join->on('py.transaction_id', '=', 'cm_energy_accounts.id');
            })
            ->leftJoin('users AS t_payee', 't_payee.id', '=', 'py.payee_id')
            ->leftJoin('users AS t_associate', 't_associate.id', '=', 'py.sponsor_id')
            ->leftJoin('customers AS t_customer', 't_customer.id', '=', 'py.user_id')
            ->leftJoin('cm_energy_account_types', function($join) {
                $join->on('cm_energy_accounts.account_type', '=', 'cm_energy_account_types.id');
            })
            ->leftJoin('cm_daily_ranks AS cdr', function($join){
                $join->on('cdr.user_id', '=', 'py.payee_id');
                $join->on('cdr.rank_date', '=', DB::raw('CURRENT_DATE'));
            })
            ->join('cm_ranks AS r', 'r.id', '=', 'cdr.rank_id')
            ->join('cm_payment_details AS cpd', 'cpd.payout_id', '=', 'py.id')
            ->join('cm_payments AS cp', 'cp.id', '=', 'cpd.payment_id')
            ->where('p.start_date', $start_date)
            ->where('p.end_date', $end_date)
            ->where('p.is_locked', static::IS_LOCKED)
            ->whereNotNull('cp.receipt_num')
            ->where('cp.is_processed', '=', 1);

        if ($user_id !== null) {
            $query = $query->where("py.payee_id", $user_id);
        }

        if ($downline_id !== null) {
            $query = $query->where("py.sponsor_id", $downline_id);
        }

        if ($commission_type_id !== "all") {
            $query = $query->where("t.id", $commission_type_id);
        }

        return $query;
    }

    public function getDownloadLink($user_id, $start_date, $end_date, $downline_id = null, $commission_type_id = null)
    {
        $csv = new CsvReport(static::REPORT_PATH);

        if (!$start_date || !$end_date) {
            $data = [];
        } else {
            $data = $this->getQuery($user_id, $start_date, $end_date, $downline_id, $commission_type_id)->get();
        }

        $filename = "historical-commission-$start_date-$end_date-";

        if ($user_id !== null) {
            $filename .= "$user_id-";
        }

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }

    public function getTotalAmount($user_id, $start_date, $end_date, $downline_id = null, $commission_type_id = null)
    {
        return $this->getQuery($user_id, $start_date, $end_date, $downline_id, $commission_type_id)->sum("py.amount");
    }
}