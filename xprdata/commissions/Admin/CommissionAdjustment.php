<?php

namespace Commissions\Admin;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use App\CommissionAdjustment as CA;

class CommissionAdjustment
{
    public function getAdjustmentHistory($request)
    {
        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;
        $draw = intval($request['draw']);
        $skip = $request['start'];
        $take = $request['length'];
        $search = $request['search'];
        $columns = $request['columns'];

        // build the query
        $query = DB::table('cm_commission_adjustments AS a')
            ->select(
                'a.id',
                DB::raw("CONCAT(u.fname, ' ',u.lname) AS name"),
                'u.id AS member_id',
                DB::raw("CONCAT(purchaser.fname, ' ', purchaser.lname) AS purchaser_name"),
                'purchaser.id AS purchaser_id',
                't.name AS commission_type',
                't.id AS commission_type_id',
                DB::raw("CONCAT(DATE_FORMAT(p.start_date, '%m/%d/%Y'), ' - ', DATE_FORMAT(p.end_date, '%m/%d/%Y')) AS commission_period"),
                'p.id AS commission_period_id',
                'a.transaction_id',
                'a.item_id',
                'a.amount',
                'a.level',
                'a.remarks',
                DB::raw('null AS actions'),
                'p.is_locked'
            )
            ->join('cm_commission_periods AS p', 'p.id', '=', 'a.commission_period_id')
            ->join('cm_commission_types AS t', 't.id', '=', 'p.commission_type_id')
            ->join('users AS u', 'u.id', '=', 'a.user_id')
            ->join('users AS purchaser', 'purchaser.id', '=', 'a.purchaser_id')
            ->where('a.is_deleted', 0);

        // count total records
        $recordsTotal = $query->count();

        // apply where
        if(isset($search) && $search['value'] != '') {
            $value = $search['value'];
            $query =
                $query->where(function($query) use ($value) {
                    $query->where('a.id', 'LIKE', "%{$value}%")
                        ->orWhereRaw('CONCAT(u.fname, \' \',u.lname) LIKE ?', ["%{$value}%"])
                        ->orWhere('t.name', 'LIKE', "%{$value}%")
                        ->orWhereRaw("CONCAT(DATE_FORMAT(p.start_date, '%m/%d/%Y'), ' - ', DATE_FORMAT(p.end_date, '%m/%d/%Y')) LIKE ?", ["%{$value}%"])
                        ->orWhere('a.amount', 'LIKE', "%{$value}%");
                });
        }

        // count total filtered records
        $recordsFiltered = $query->count();

        // apply limit
        $query = $query->take($take);

        // apply offset
        if($skip) $query = $query->skip($skip);

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function purchaser($term)
    {
        $qry = DB::table('transactions');
        if (is_numeric($term)) {
            $qry->where('userid', '=', $term);
        } else {
            $qry->where(function($qry) use($term) {
                $qry->orwhere('billfname', 'like', '%' . $term . '%')
                    ->orwhere('billlname', 'like', '%' . $term . '%');
            });
        }

        $result = $qry->selectRaw('id as `userid`, concat(userid, \'-\', billfname, \' \', billlname) as `label`')
            ->take(10)->get();
        return $result;
    }

    public function save($data, $processed_by_id = null)
    {
        $adjustment = DB::transaction(function () use ($data, $processed_by_id) {

            $adjustment = new CA();
            $adjustment->commission_period_id = $data['commission_period_id'];
            $adjustment->transaction_id = $data['order_id'];
            $adjustment->user_id = $data['member_id'];
            $adjustment->level = $data['level'];
            $adjustment->amount = $data['amount'];
            $adjustment->item_id = $data['item_id'];
            $adjustment->remarks = $data['remarks'];
            $adjustment->created_by_id = $processed_by_id;
            $adjustment->purchaser_id = $data['purchaser_id'];
            $adjustment->save();

            return $adjustment;
        });

        return $adjustment;
    }

    public function update($data, $processed_by_id = null)
    {
        $adjustment = DB::transaction(function () use ($data, $processed_by_id) {

            $adjustment = CA::findOrFail($data['id']);;
            $adjustment->commission_period_id = $data['commission_period_id'];
            $adjustment->transaction_id = $data['order_id'];
            $adjustment->user_id = $data['member_id'];
            $adjustment->level = $data['level'];
            $adjustment->amount = $data['amount'];
            $adjustment->item_id = $data['item_id'];
            $adjustment->remarks = $data['remarks'];
            $adjustment->created_by_id = $processed_by_id;
            $adjustment->purchaser_id = $data['purchaser_id'];
            $adjustment->save();

            return $adjustment;
        });

        return $adjustment;
    }

    public function delete($id, $processed_by_id = null)
    {
        return CA::where('id', $id)->where('is_deleted', 0)->update([
            'is_deleted' => 1,
            'deleted_at' => Carbon::now(),
            'deleted_by_id' => $processed_by_id
        ]);
    }

}