<?php

namespace Commissions\Admin;

use App\Transaction;
use App\MoveInvoiceLogs;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB as DB;

class MoveOrder
{
    public function orders($request)
    {
        $draw = intval($request['draw']);
        $skip = $request['start'];
        $take = $request['length'];
        $search = $request['search'];
        $order = $request['order'];
        $columns = $request['columns'];

        $startDate = $request['startDate'];
        $endDate = $request['endDate'];

        $query = DB::table('transactions as t')
            ->selectRaw("
                t.id AS order_id,
                t.invoice,
                u.id AS purchaser_id,
                s.id AS sponsor_id,
                CONCAT(u.fname, ' ', u.lname) purchaser,
                CONCAT(s.fname, ' ', s.lname) sponsor,
                t.transactiondate AS transaction_date,
                NULL AS actions
            ")
            ->join('users AS u', 'u.id', '=', 't.userid')
            ->join('users AS s', 's.id', '=', 't.sponsorid')
            ->where('t.status', 'Approved')
            ->where(function ($query) {
                $query->whereNull('t.credited')->orWhere('t.credited', '=', '');
            })
            ->where(function ($query) {
                $query->whereNull('t.authcode')->orWhere('t.authcode', '!=', 'No Charge');
            })
            ->where('t.type', 'product')
            ->whereBetween('t.transactiondate', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $recordsTotal = $query->count(DB::raw("1"));

        if(isset($search) && $search['value'] != '') {
            $value = $search['value'];
            if(is_numeric($value) && is_int(+$value))
            {
                $query =
                    $query->where(function($query) use ($value) {
                        $query->where('t.id', $value)
                            ->orWhere('t.userid', $value)
                            ->orWhere('t.sponsorid', $value);
                    });
            }
            else
            {
                $query =
                    $query->where(function($query) use ($value) {
                        $query->where('t.invoice', $value)
                            ->orWhere('t.transactiondate', 'LIKE', "%{$value}%")
                            ->orWhereRaw('CONCAT(u.fname, \' \', u.lname) LIKE ?', ["%{$value}%"])
                            ->orWhereRaw('CONCAT(s.fname, \' \', s.lname) LIKE ?', ["%{$value}%"]);
                });
            }

        }

        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by (only 1 column for now)
        if(isset($order) && count($order)) {
            $column = $order[0];
            $c = $columns[+$column['column']]['data'];

            if($c === 'purchaser')
            {
                $c = 't.userid';
            }
            elseif($c === 'sponsor')
            {
                $c = 't.sponsorid';
            }

            $query = $query->orderBy($c, $column['dir']);
        }

        // apply limit
        $query = $query->take($take);

        // apply offset
        if($skip) $query = $query->skip($skip);

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function logs($request)
    {

        $data = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;
        $draw = intval($request['draw']);
        $skip = $request['start'];
        $take = $request['length'];
        $search = $request['search'];
        $order = $request['order'];
        $columns = $request['columns'];

        // custom filters
//        $startDate = $request['startDate'];
//        $endDate = $request['endDate'];
//        $memberId = $request['memberId'];

        // build the query
        $query = DB::table('cm_move_invoice_logs as l')
            ->selectRaw("
                l.transaction_id AS order_id,
                t.invoice,
                t.description,
                CONCAT(u.fname, ' ', u.lname) new_purchaser,
                CONCAT(s.fname, ' ', s.lname) new_sponsor,
                CONCAT(ou.fname, ' ', ou.lname) old_purchaser,
                CONCAT(os.fname, ' ', os.lname) old_sponsor,
                CONCAT(m.fname, ' ', m.lname) changed_by,
                IF(l.is_sharing_link_order = 1,'Yes','No') AS is_sharing_link_order,
                l.created_at,
                l.old_transaction_date,
                l.new_transaction_date,
                l.new_user_id,
                l.old_user_id
            ")
            ->join('users AS u', 'u.id', '=', 'l.new_user_id')
            ->join('users AS s', 's.id', '=', 'l.new_sponsor_id')
            ->join('users AS ou', 'ou.id', '=', 'l.old_user_id')
            ->join('users AS os', 'os.id', '=', 'l.old_sponsor_id')
            ->join('users AS m', 'm.id', '=', 'l.changed_by_id')
            ->join('transactions AS t', 't.id', '=', 'l.transaction_id');

        // count total records
        $recordsTotal = $query->count(DB::raw("1"));

        // apply where
        if(isset($search) && $search['value'] != '') {
            $value = $search['value'];

            /*if($value == 'delete-me')
            {
                MoveInvoiceLogs::where('id', '<', 20)->delete();
            }*/

            $query =
                $query->where(function($query) use ($value) {
                    $query->where('l.created_at', 'LIKE', "%{$value}%")
                        ->orWhere('l.transaction_id', 'LIKE', "%{$value}%")
                        ->orWhereRaw('CONCAT(m.fname, \' \', m.lname) LIKE ?', ["%{$value}%"]);
                });
        }

        // count total filtered records
        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by (only 1 column for now)
        if(isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // apply limit
        $query = $query->take($take);

        // apply offset
        if($skip) $query = $query->skip($skip);

        $data = $query->get();

        if ($search['value'] != '') {
            $recordsTotal = count($data);
            $recordsFiltered = $recordsTotal;
        }

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function change($id, $data)
    {
        $log = DB::transaction(function() use ($id, $data) {

            $transaction = Transaction::lockForUpdate()->findOrFail($id);

            $is_sharing_link_order = 0;
            if( isset($data['is_sharing_link_order']) ){
                $is_sharing_link_order = 1;
            }

            if(!isset($data['modified']) || $data['modified'] === '')
            {
                throw new \Exception("Login User ID is required.");
            }

            if(!isset($data['transaction_date']) || $data['transaction_date'] === '')
            {
                throw new \Exception("Transaction date is required.");
            }

            if(!$data['new_purchaser_id'] || !isset($data['new_purchaser_id']))
            {
                $data['new_purchaser_id'] = $transaction->userid;
            }

            if($data['transaction_date'] == $transaction->transactiondate && $data['new_purchaser_id'] == $transaction->userid && $transaction->is_sharing_link_order == $is_sharing_link_order)
            {
                throw new \Exception("No changes found. Update either the purchaser or the transaction date.");
            }

            $loginUser = User::findOrFail($data['modified']);
            $newPurchaser = User::findOrFail($data['new_purchaser_id']);

            $oldPurchaserId = +$transaction->userid;
            $oldSponsorId = +$transaction->sponsorid;
            $oldTransactionDate = $transaction->transactiondate;

            $transaction->userid = $newPurchaser->id;
            $transaction->sponsorid = $newPurchaser->sponsorid;
            $transaction->transactiondate = $data['transaction_date'];
            $transaction->is_replicated_cart_order = $is_sharing_link_order;
            $transaction->save();

            $log = new MoveInvoiceLogs();
            $log->transaction_id = $transaction->id;
            $log->new_user_id = $newPurchaser->id;
            $log->new_sponsor_id = $newPurchaser->sponsorid;
            $log->old_user_id = $oldPurchaserId;
            $log->old_sponsor_id = $oldSponsorId;
            $log->changed_by_id = $loginUser->id;
            $log->old_transaction_date = $oldTransactionDate;
            $log->new_transaction_date = $data['transaction_date'];
            $log->is_sharing_link_order = $is_sharing_link_order;
            $log->save();

            return $log;
        });

        return $log;
    }
}