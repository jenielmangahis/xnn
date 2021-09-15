<?php


namespace Commissions\Admin;


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

        $query = DB::table('cm_energy_accounts as cm_ea')
            //customer
            ->leftJoin('users as cust', function($join) {
                $join->on('cm_ea.customer_id', '=', 'cust.id');
            })

            //associates
            ->leftJoin('users as assoc', function($join) {
                $join->on('cm_ea.sponsor_id', '=', 'assoc.id');
            })

            //energy account types
            ->leftJoin('cm_energy_account_types as cm_eat', function($join) {
                $join->on('cm_ea.energy_type', '=', 'cm_eat.id');
            })

            //energy account logs date accepted
            ->join(DB::raw("(
                SELECT id, reference_id, MIN(updated_at) as updated_at FROM cm_energy_account_logs
                WHERE current_status = 4)  AS cm_eal_accepted 
            "),
                function($join) {
                    $join->on('cm_ea.reference_id', '=', 'cm_eal_accepted.reference_id');
                }
            )

            //energy account logs date flowing
            ->join(DB::raw("(
                SELECT id, reference_id, MIN(updated_at) as updated_at FROM cm_energy_account_logs
                WHERE current_status = 5)  AS cm_eal_flowing 
            "),
                function($join) {
                    $join->on('cm_ea.reference_id', '=', 'cm_eal_flowing.reference_id');
                }
            )

            //energy account logs
            ->join('cm_energy_account_logs as cm_eal', function($join) {
                $join->on('cm_ea.reference_id', '=', 'cm_eal.reference_id');
            })

            ->join('cm_energy_account_status_types as cm_east', function($join){
                $join->on('cm_east.id', '=', 'cm_eal.current_status');
            })
            
            ->selectRaw("
                cm_ea.customer_id,
                CONCAT(cust.fname, ' ', cust.lname) as customer,
                CONCAT(assoc.fname, ' ', assoc.lname) as associate,
                cm_eat.type as account,
                cm_eal_accepted.updated_at as date_accepted,
                cm_eal_flowing.updated_at as date_started_flowing,
                cm_east.type as status
            ");

        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('cm_ea.customer_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('customer', 'LIKE', "%{$search}%")
                    ->orWhere('associate', 'LIKE', "%{$search}%")
                    ->orWhere('account', 'LIKE', "%{$search}%")
                    ->orWhere('date_accepted', 'LIKE', "%{$search}%")
                    ->orWhere('date_started_flowing', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%");
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

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getEnergyAccountStatus($filters, $userId = null) {
        return DB::table('cm_energy_account_status_types')
            ->select('id', 'type')
            ->get();
    }

    public function getStatusCount() {
        $pendingConfirmationCount = DB::table('cm_energy_account_logs')
            ->where('current_status', 1)
            ->groupBy('reference_id')
            ->count();

        $pendingApprovalCount = DB::table('cm_energy_account_logs')
            ->where('current_status', 2)
            ->groupBy('reference_id')
            ->count();

        $pendingRejectionCount = DB::table('cm_energy_account_logs')
            ->where('current_status', 3)
            ->groupBy('reference_id')
            ->count();

        $approvedPendingFlowingCount = DB::table('cm_energy_account_logs')
            ->where('current_status', 4)
            ->groupBy('reference_id')
            ->count();

        $flowingCount = DB::table('cm_energy_account_logs')
            ->where('current_status', 5)
            ->groupBy('reference_id')
            ->count();

        $flowingPendingCancellationCount = DB::table('cm_energy_account_logs')
            ->where('current_status', 6)
            ->groupBy('reference_id')
            ->count();
            
        $cancelledCount = DB::table('cm_energy_account_logs')
            ->where('current_status', 7)
            ->groupBy('reference_id')
            ->count();
        
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

    public function showStatus($referenceId) {
        return ['x' => $referenceId];
        return DB::table('cm_energy_account_logs as cm_eal')
            ->leftJoin('cm_energy_account_status_types', function($join){
                $join->on('cm_east.id', '=', 'cm_eal.current_status');
            })
            ->select('cm_east.type', 'cm_eal.updated_at')
            ->where('reference_id', $referenceId)
            ->get();
    }
}