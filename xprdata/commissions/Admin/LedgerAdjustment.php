<?php


namespace Commissions\Admin;


use App\Ledger;
use App\LedgerAdjustment as LA;
use Illuminate\Support\Facades\DB;

class LedgerAdjustment
{
    public function getAdjustments($filters)
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

        $query = DB::table('cm_ledger_adjustment AS la')
            ->selectRaw("
                la.id,
                la.user_id,
                la.description AS notes,
                la.amount,
                la.created_by created_by_id,
                la.updated_at,
                CONCAT(u.fname, ' ', u.lname) member,
                IF(c.id IS NOT NULL, CONCAT(c.fname, ' ', c.lname), NULL) created_by
            ")
            ->join('users AS u', 'u.id', '=', 'la.user_id')
            ->leftJoin('users AS c', 'c.id', '=', 'la.created_by')
            ->where("la.is_deleted", 0);


        $recordsTotal = $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {
            $query->where(function ($query) use ($search) {
                $query->where('la.user_id', $search)
                    ->orWhere('la.created_by', $search);
            });
        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('c.fname', 'LIKE', "%{$search}%")
                    ->orWhere('c.lname', 'LIKE', "%{$search}%")
                    ->orWhere('la.description', 'LIKE', "%{$search}%")
                ;
            });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by
        // order by 1 column

        if (count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // default order by
        $query = $query->orderBy('la.id', 'desc');

        $query = $query->take($take);

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function saveAdjustment($user_id, $description, $amount, $type, $created_by)
    {
        if ($amount <= 0) {
            throw new \Exception('Amount should be greater than 0');
        }

        $adjustment = DB::transaction(function () use ($user_id, $description, $amount, $type, $created_by) {

            if($type === 'remove') {
                $amount *= -1;
            }

            $adjustment = new LA();
            $adjustment->user_id = $user_id;
            $adjustment->amount = $amount;
            $adjustment->description = $description;
            $adjustment->created_by = $created_by;
            $adjustment->save();

            $ledger = new Ledger();
            $ledger->user_id = $user_id;
            $ledger->amount = $amount;
            $ledger->notes = $description;
            $ledger->type = Ledger::TYPE_ADJUSTMENT;
            $ledger->reference_number = $adjustment->id;
            $ledger->save();

            $adjustment->ledger_id = $ledger->id;
            $adjustment->save();

            return $adjustment;
        });

        return $adjustment;
    }

    public function deleteAdjustment($id, $deleted_by)
    {
        $undoAdjustment = DB::transaction(function () use ($id, $deleted_by) {
            $undoAdjustment = LA::findOrFail($id);
            $undoAdjustment->is_deleted = 1;
            $undoAdjustment->deleted_by = $deleted_by;
            $undoAdjustment->save();

            $adjustment = new LA();
            $adjustment->user_id = $undoAdjustment->user_id;
            $adjustment->amount = $undoAdjustment->amount * -1;
            $adjustment->description = $undoAdjustment->description . " (UNDO ADJUSTMENT)";
            $adjustment->created_by = $undoAdjustment->created_by;
            $adjustment->is_deleted = 1;
            $adjustment->save();

            $ledger = new Ledger();
            $ledger->user_id = $undoAdjustment->user_id;
            $ledger->amount = $undoAdjustment->amount * -1;
            $ledger->notes = $undoAdjustment->description . " (UNDO ADJUSTMENT)";
            $ledger->type = Ledger::TYPE_ADJUSTMENT;
            $ledger->reference_number = $adjustment->id;
            $ledger->save();

            $adjustment->ledger_id = $ledger->id;
            $adjustment->save();
        });

        return $undoAdjustment;
    }
}