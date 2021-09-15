<?php


namespace Commissions\Admin;

use Exception;
use Illuminate\Support\Facades\DB;

class EnergyAccount
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getEnergyAccounts($filters, $userId = null)
    {
		// return [];
        // DB::enableQueryLog();
        $data = [];
        $recordsTotal = $recordsFiltered = 0;

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        //VARIABLES START
        $startDate = isset($filters['start_date']) ? $filters['start_date'] : null;
        $endDate = isset($filters['end_date']) ? $filters['end_date'] : null;
        $statusId = (int) $filters['status_id'];
        //VARIABLES END

        $query = $this->getQuery($statusId,$startDate,$endDate);

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('cm_ea.customer_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('cust.fname', 'LIKE', "%{$search}%")
                    ->orWhere('cust.lname', 'LIKE', "%{$search}%")
                    ->orWhere('cust.business', 'LIKE', "%{$search}%")
                    ->orWhere('cust.memberid', 'LIKE', "%{$search}%")
                    ->orWhere('cm_ea.reference_id', 'LIKE', "%{$search}%")
                    ->orWhere('assoc.lname', 'LIKE', "%{$search}%")
                    ->orWhere('assoc.fname', 'LIKE', "%{$search}%")
                    ->orWhere('cm_eat.type', 'LIKE', "%{$search}%")
                    ->orWhere('cm_eal_accepted.created_at', 'LIKE', "%{$search}%")
                    ->orWhere('cm_eal_flowing.created_date', 'LIKE', "%{$search}%")
                    ->orWhere('cm_east.type', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("cm_ea.customer_id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();
        $dumpSql = $query->toSql();

        // return ['yawa' => $data, 'angel' => $queries = DB::getQueryLog()];

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'dumpSql');
    }

    public function getQuery($status_id,$start_date,$end_date) {
        $query = DB::table('cm_energy_accounts as cm_ea')
            //customer
            ->leftJoin('customers as cust', function($join) {
                $join->on('cm_ea.customer_id', '=', 'cust.id');
            })

            //associates
            ->leftJoin('users as assoc', function($join) {
                $join->on('cm_ea.sponsor_id', '=', 'assoc.id');
            })

            //associates
            ->leftJoin('users as assoc_sponsor', function($join) {
                $join->on('assoc.sponsorid', '=', 'assoc_sponsor.id');
            })

            //energy types
            ->leftJoin('cm_energy_types as cm_eat', function($join) {
                $join->on('cm_ea.energy_type', '=', 'cm_eat.id');
            })

            //energy account logs date accepted
            ->leftJoin("cm_energy_account_logs AS cm_eal_accepted",
                function($join) {
                    $join->on('cm_ea.id', '=', 'cm_eal_accepted.energy_account_id');
                    $join->whereRaw("cm_eal_accepted.id = (SELECT l1.id FROM cm_energy_account_logs l1 WHERE l1.energy_account_id = cm_ea.id AND l1.current_status = 4 ORDER BY l1.created_at ASC LIMIT 1)");
                }
            )

            //energy account logs date flowing
            ->leftJoin("cm_energy_account_logs AS cm_eal_flowing",
                function($join) {
                    $join->on('cm_ea.id', '=', 'cm_eal_flowing.energy_account_id');
                    $join->whereRaw("cm_eal_flowing.id = (SELECT l1.id FROM cm_energy_account_logs l1 WHERE l1.energy_account_id = cm_ea.id AND l1.current_status = 5 ORDER BY l1.created_at ASC LIMIT 1)");
                }
            )

            //energy account logs
            /* ->join('cm_energy_account_logs as cm_eal', function($join) {
                $join->on('cm_ea.id', '=', 'cm_eal.energy_account_id');
                $join->whereRaw("cm_eal.id = (SELECT l1.id FROM cm_energy_account_logs l1 WHERE l1.energy_account_id = cm_ea.id AND l1.current_status = cm_eal.current_status ORDER BY l1.created_at ASC LIMIT 1)");
            })*/

            ->join('cm_energy_account_status_types as cm_east', function($join){
                $join->on('cm_east.id', '=', 'cm_ea.status');
            })

            ->selectRaw("
                cm_ea.id AS energy_id,
                cm_ea.reference_id,
                cust.memberid as customer_id,
                IF(CONCAT(cust.fname, ' ', cust.lname) = '', cust.business, IFNULL(CONCAT(cust.fname, ' ', cust.lname), cust.business)) as customer,
                CONCAT(assoc.fname, ' ', assoc.lname) as associate,
                assoc.id as associate_id,
                CONCAT(assoc_sponsor.id, '. ', assoc_sponsor.fname, ' ', assoc_sponsor.lname) as associate_sponsor,
                cm_eat.type as account,
                cm_eal_accepted.created_at as date_accepted,
                cm_eal_flowing.created_date as date_started_flowing,
                cm_east.type as status")
            ->whereBetween("cm_ea.created_at", [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);

        if($status_id) {
            $query->where("cm_ea.status", $status_id);
        }

        return $query;
    }

    public function getEnergyAccountStatus($filters, $userId = null) {
        return DB::table('cm_energy_account_status_types')
            ->select('id', 'type')
            ->get();
    }

    public function getStatusCount($filters) {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $status_id = isset($filters['status_id']) ? +$filters['status_id'] : null;

        $pendingConfirmationCount = 0;
        $pendingApprovalCount = 0;
        $pendingRejectionCount = 0;
        $approvedPendingFlowingCount = 0;
        $flowingCount = 0;
        $flowingPendingCancellationCount = 0;
        $cancelledCount = 0;

        if($status_id === 1) {
            $pendingConfirmationCount = $this->getQuery($status_id,$startDate,$endDate)->count(DB::raw("1"));
        }elseif ($status_id === 2) {
            $pendingApprovalCount = $this->getQuery($status_id,$startDate,$endDate)->count(DB::raw("1"));
        }elseif ($status_id === 3) {
            $pendingRejectionCount = $this->getQuery($status_id,$startDate,$endDate)->count(DB::raw("1"));
        }elseif ($status_id === 4) {
            $approvedPendingFlowingCount = $this->getQuery($status_id,$startDate,$endDate)->count(DB::raw("1"));
        }elseif ($status_id === 5) {
            $flowingCount = $this->getQuery($status_id,$startDate,$endDate)->count(DB::raw("1"));
        }elseif ($status_id === 6) {
            $flowingPendingCancellationCount = $this->getQuery($status_id,$startDate,$endDate)->count(DB::raw("1"));
        }elseif ($status_id === 7) {
            $cancelledCount = $this->getQuery($status_id,$startDate,$endDate)->count(DB::raw("1"));
        }else {
            $pendingConfirmationCount = $this->getQuery(1,$startDate,$endDate)->count(DB::raw("1"));
            $pendingApprovalCount = $this->getQuery(2,$startDate,$endDate)->count(DB::raw("1"));
            $pendingRejectionCount = $this->getQuery(3,$startDate,$endDate)->count(DB::raw("1"));
            $approvedPendingFlowingCount = $this->getQuery(4,$startDate,$endDate)->count(DB::raw("1"));
            $flowingCount = $this->getQuery(5,$startDate,$endDate)->count(DB::raw("1"));
            $flowingPendingCancellationCount = $this->getQuery(6,$startDate,$endDate)->count(DB::raw("1"));
            $cancelledCount = $this->getQuery(7,$startDate,$endDate)->count(DB::raw("1"));
        }

        return [
            'pending_confirmation_count' => $pendingConfirmationCount,
            'pending_approval_count' => $pendingApprovalCount,
            'pending_rejection_count' => $pendingRejectionCount,
            'approved_pending_flowing_count' => $approvedPendingFlowingCount,
            'flowing_count' => $flowingCount,
            'flowing_pending_cancellation' => $flowingPendingCancellationCount,
            'cancelled_count' => $cancelledCount,
        ];
    }

    public function showStatus($energy_id) {
        return DB::table('cm_energy_account_logs as cm_eal')
            ->leftJoin('cm_energy_account_status_types as cm_east', function($join){
                $join->on('cm_east.id', '=', 'cm_eal.current_status');
            })
            ->selectRaw('cm_east.type, cm_eal.created_date')
            ->where('cm_eal.energy_account_id', $energy_id)
            ->get();
    }
}