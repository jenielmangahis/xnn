<?php


namespace Commissions\Member;


use App\DailyVolume;
use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;

class DetailedCommission
{
    const REPORT_PATH = "csv/member/detailed_commission";

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getDetailedCommission($filters, $user_id)
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
        $commission_type_id = isset($filters['commission_type_id']) ? $filters['commission_type_id'] : "";

        if (!$start_date && !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date');
        }

        if ($commission_type_id === '0') {
            $commission_type_id = DB::table('cm_commission_group_types')->selectRaw("GROUP_CONCAT(type_id) AS commission_type_ids")->first()->commission_type_ids;
        }

        $query = $this->getDetailedCommissionQuery($user_id, $start_date, $end_date, $commission_type_id);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('p.user_id', $search)
                    ->orWhere('p.level', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('cet.type', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("p.level", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();
        $dumpSql = $query->toSql();
        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'dumpSql', 'commission_type_id');
    }

    protected function getDetailedCommissionQuery($user_id, $start_date, $end_date, $commission_type_id)
    {

        $query =
            DB::table('cm_commission_payouts AS p')
            ->join("cm_payment_details AS pd", "pd.payout_id", "=", "p.id")
            ->join("cm_payments AS py", "py.id", "=", "pd.payment_id")
            ->join("cm_payment_history AS ph", "ph.id", "=", "py.history_id")
            ->join('cm_commission_periods AS cp', 'cp.id', '=', 'p.commission_period_id')
            ->join('cm_commission_types AS ct', 'ct.id', '=', 'cp.commission_type_id')
            ->join('cm_commission_group_types AS gt', 'gt.type_id', '=', 'ct.id')
            ->join('cm_commission_groups AS cg', 'cg.id', '=', 'gt.group_id')
            ->leftJoin("users AS u", "u.id", "=", "p.user_id")
            ->leftJoin("users AS s", "s.id", "=", "p.payee_id")
            ->leftJoin("cm_energy_accounts AS cea", "cea.customer_id", "=", "p.user_id")
            ->leftJoin('cm_energy_types AS cet', 'cet.id', '=', 'cea.energy_type')
            ->leftJoin("cm_energy_account_logs AS cm_eal_accepted",
                function($join) {
                    $join->on('cea.id', '=', 'cm_eal_accepted.energy_account_id');
                    $join->whereRaw("cm_eal_accepted.id = (SELECT l1.id FROM cm_energy_account_logs l1 WHERE l1.energy_account_id = cea.id AND l1.current_status = 4 ORDER BY l1.created_at ASC LIMIT 1)");
                }
            )
            ->leftJoin("cm_energy_account_logs AS cm_eal_flowing",
                function($join) {
                    $join->on('cea.id', '=', 'cm_eal_flowing.energy_account_id');
                    $join->whereRaw("cm_eal_flowing.id = (SELECT l1.id FROM cm_energy_account_logs l1 WHERE l1.energy_account_id = cea.id AND l1.current_status = 4 ORDER BY l1.created_at ASC LIMIT 1)");
                }
            )
            ->selectRaw("
                cg.name AS group_name,
                ct.name AS type_name,
                p.level,
                CONCAT(s.fname,' ',s.lname) AS associates_name,
                p.payee_id,
                IF(CONCAT(u.fname, ' ', u.lname) = '', CONCAT(LEFT(u.business,5),REPEAT('*', CHAR_LENGTH(u.business))), IFNULL(CONCAT(u.fname, ' ', u.lname), CONCAT(LEFT(u.business,5),REPEAT('*', CHAR_LENGTH(u.business))))) AS customer_name,
                CONCAT(REPEAT('*', CHAR_LENGTH(cea.reference_id) - 4), SUBSTR(cea.reference_id, CHAR_LENGTH(cea.reference_id) - 4)) AS por,
                cet.type,
                py.total_gross AS gross_amount,
                cm_eal_accepted.created_at as date_accepted,
                cm_eal_flowing.created_at as date_flowing,
                py.receipt_num
            ")
            ->where('ph.status', 'COMPLETED')
            ->whereRaw('FIND_IN_SET(cp.commission_type_id, ?)' , [$commission_type_id])
            // ->where('cp.commission_type_id', $commission_type_id)
           // ->where('cea.status', 4) //get Approved, Pending Flowing status only
            ->whereBetween('cp.end_date',[$start_date, $end_date])
            ->groupBy('cp.commission_type_id');

        if (!!$user_id) {
            $query->whereRaw("EXISTS(
                WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`
                    FROM users
                    WHERE id = ? AND levelid = 3
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE p.levelid = 3
                )
                SELECT 1 FROM downline d WHERE d.user_id = p.user_id
            )", [$user_id]);
        }

        return $query;
    }


    public function getDownloadLink($user_id, $start_date, $end_date, $commission_type_id)
    {
        $csv = new CsvReport(static::REPORT_PATH);

        if (!$start_date && !$end_date) {
            $data = [];
        } else {
            $data = $this->getDetailedCommissionQuery($user_id, $start_date, $end_date, $commission_type_id)->get();
        }

        $filename = "detailed-commission-report-$start_date-$end_date";

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }
}