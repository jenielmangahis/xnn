<?php


namespace Commissions\Admin;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\MinimumRank as MR;

class MinimumRank
{
    public function getMinimumRanks($filters)
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

        $query = DB::table('cm_minimum_ranks AS mr')
            ->selectRaw("
                mr.id,
                mr.user_id,
                mr.rank_id,
                mr.start_date,
                mr.end_date,
                mr.created_by_id,
                mr.updated_at,
                IF(r.name = 'Affiliate', 'IBO', r.name) AS minimum_rank,
                CONCAT(u.fname, ' ', u.lname) member,
                IF(c.id IS NOT NULL, CONCAT(c.fname, ' ', c.lname), NULL) created_by
            ")
            ->join('cm_ranks AS r', 'r.id', '=', 'mr.rank_id')
            ->join('users AS u', 'u.id', '=', 'mr.user_id')
            ->leftJoin('users AS c', 'c.id', '=', 'mr.created_by_id')
            ->where("mr.is_deleted", "0");


        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('mr.user_id', $search)
                    ->orWhere('mr.created_by_id', $search);
            });
        } 
        elseif (!!$search) {
            if(stripos($search, 'aff') !== false) {
                $query->where('r.name', '');
            }
            else {
                $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('c.fname', 'LIKE', "%{$search}%")
                    ->orWhere('c.lname', 'LIKE', "%{$search}%")
                    // ->orWhere('r.name', 'LIKE', "%{$search}%")
                    ->orWhereRaw('r.name', 'LIKE', "%{$search}%")
                    ->orWhere('mr.start_date', 'LIKE', "%{$search}%")
                    ->orWhere('mr.end_date', 'LIKE', "%{$search}%")
                    ;
                });
            }
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by
        // order by 1 column

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('mr.id', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function save($data, $processed_by_id = null)
    {
        $minimum = DB::transaction(function() use ($data, $processed_by_id) {

            MR::where("user_id", $data['user_id'])->where('is_deleted', 0)->update([
                'is_deleted' => 1,
                'deleted_at' => Carbon::now(),
            ]);

            $minimum = new MR();
            $minimum->user_id = $data['user_id'];
            $minimum->rank_id = $data['rank_id'];
            $minimum->start_date = $data['start_date'];
            $minimum->end_date = $data['end_date'];
            $minimum->created_by_id = $processed_by_id;

            $minimum->save();

            return $minimum;
        });

        return $minimum;
    }

    public function delete($user_id, $processed_by_id = null)
    {
        return MR::where("user_id", $user_id)->where('is_deleted', 0)->update([
            'is_deleted' => 1,
            'deleted_at' => Carbon::now(),
            'deleted_by_id' => $processed_by_id
        ]);
    }

    public function getMinimumRank($user_id)
    {
        return  MR::where("user_id", $user_id)->where('is_deleted', 0)->first();
    }
}