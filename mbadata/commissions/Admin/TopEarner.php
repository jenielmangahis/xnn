<?php

namespace Commissions\Admin;
use Illuminate\Support\Facades\DB;
use PDO;

class TopEarner 
{
    protected $db;
    const IS_LOCKED = 1;
    
    public function getTopEarners($filters, $user_id = null)
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
        $is_all = isset($filters['is_all']) ? +$filters['is_all'] : 0;

        if (!$start_date || !$end_date) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'start_date');
        }

        $query = $this->getTopEarnersQuery($user_id, $start_date, $end_date, $is_all);

        $recordsTotal = DB::table(DB::raw("({$query->toSql()}) as sub") )
            ->mergeBindings($query)
            ->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('u.id', $search)
                    ->orWhere('u.sponsorid', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->Where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $recordsTotal;

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("u.id", "ASC");

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'user_id', 'start_date', 'end_date', 'is_all', 'rank_id');
    }

    
    protected function getTopEarnersQuery($user_id, $start_date, $end_date, $is_all)
    {

        if ($end_date > date('Y-m-d')) {
            $end_date = date('Y-m-d');
        }

        if ($start_date > date('Y-m-d')) {
            $start_date = date('Y-m-d');
        }

        $query = DB::table('cm_commission_payouts AS py')
            ->selectRaw("
                u.id AS user_id,
                CONCAT(u.fname, ' ', u.lname) AS member,
                u.site,
                SUM(py.`amount`) AS earnings
            ")
            ->join('cm_commission_periods AS p', 'p.id', '=', 'py.commission_period_id')
            ->join('users AS u', 'u.id', '=', 'py.payee_id')
            ->where('p.is_locked', static::IS_LOCKED)
            ->groupBy('u.id');

        if (!$is_all) {
            $query->whereBetween('p.end_date', [$start_date, $end_date]);
        }

        return $query;
    }
}