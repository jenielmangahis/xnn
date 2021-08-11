<?php

namespace Commissions\Member;
use App\LedgerTransfer;
use App\LedgerWithdrawal;
use App\Mail\LedgerAdded;
use App\User;
use Commissions\Exceptions\CommissionException;
use Illuminate\Support\Facades\DB;
use App\Ledger as LedgerModel;
use Illuminate\Support\Facades\Mail;

class Ledger
{
    public function ledger($filters, $member_id)
    {
        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('cm_ledger AS l')
            ->selectRaw("
                -- DATE_FORMAT(l.created_at, '%Y/%m/%d') AS date,
                l.created_at AS date,
                l.notes,
                l.amount
            ")
            ->where('l.user_id', $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        if(isset($search) && $search['value'] != '') {
            $value = $search['value'];
            $query =
                $query->where(function($query) use ($value) {
                    $query->where('l.notes','LIKE', "%{$value}%");
                });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if(isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("l.id", "desc");

        $query = $query->take($take);

        if($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function withdrawal($filters, $member_id)
    {
        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search = $filters['search'];
        $order = $filters['order'];
        $columns = $filters['columns'];

        $query = DB::table('cm_ledger_withdrawal AS w')
            ->selectRaw("
                w.created_at AS date,
                w.amount,
                w.status
            ")
            ->where('w.user_id', $member_id);

        $recordsTotal = $query->count(DB::raw("1"));

        if(isset($search) && $search['value'] != '') {
            $value = $search['value'];
            $query =
                $query->where(function($query) use ($value) {
                    $query->where('w.status','LIKE', "%{$value}%");
                });
        }

        $recordsFiltered = $query->count(DB::raw("1"));

        if(isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("w.id", "desc");

        $query = $query->take($take);

        if($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getTotalBalance($member_id)
    {
        return LedgerModel::ofMember($member_id)->sum('amount');
    }

    public function transfer($member_id, $amount, $transfer_member_id)
    {
        $ledger = DB::transaction(function() use ($member_id, $amount, $transfer_member_id){
            $total_balance = LedgerModel::ofMember($member_id)->lockForUpdate()->sum('amount');

            if($amount > $total_balance) {
                throw new CommissionException("Insufficient Funds!");
            }

            if(+$member_id === +$transfer_member_id) {
                throw new CommissionException("Transferring fund to own account is not allowed.");
            }

            $member = User::findOrFail($member_id);
            $transfer_member = User::findOrFail($transfer_member_id);

            $transfer = new LedgerTransfer();
            $transfer->user_id = $member_id;
            $transfer->transfer_user_id = $transfer_member_id;
            $transfer->amount = $amount;
            $transfer->save();

            $ledger = new LedgerModel();
            $ledger->user_id = $member_id;
            $ledger->amount = $amount * -1;
            $ledger->notes = "Transfer to Username: {$transfer_member->site}";
            $ledger->type = LedgerModel::TYPE_TRANSFER;
            $ledger->reference_number = $transfer->id;
            $ledger->save();

            $transfer_ledger = new LedgerModel();
            $transfer_ledger->user_id = $transfer_member_id;
            $transfer_ledger->amount = $amount;
            $transfer_ledger->notes = "Receive from Username: {$member->site}";
            $transfer_ledger->type = LedgerModel::TYPE_TRANSFER;
            $transfer_ledger->reference_number = $transfer->id;
            $transfer_ledger->save();

            $transfer->ledger_id = $ledger->id;
            $transfer->transfer_ledger_id = $transfer_ledger->id;
            $transfer->save();

            return $ledger;
        });

        return $ledger;
    }

    public function withdraw($member_id, $amount)
    {
        $ledger = DB::transaction(function() use ($member_id, $amount){
            $total_balance = LedgerModel::ofMember($member_id)->lockForUpdate()->sum('amount');

            if($amount > $total_balance) {
                throw new CommissionException("Insufficient Funds!");
            }

            $withdraw = new  LedgerWithdrawal();
            $withdraw->user_id = $member_id;
            $withdraw->amount = $amount;
            $withdraw->status = LedgerWithdrawal::STATUS_PENDING;
            $withdraw->save();

            $ledger = new LedgerModel();
            $ledger->user_id = $member_id;
            $ledger->amount = $amount * -1;
            $ledger->notes = "Funds Withdrawn";
            $ledger->type = LedgerModel::TYPE_WITHDRAWAL;
            $ledger->reference_number = $withdraw->id;
            $ledger->save();

            $withdraw->ledger_id = $ledger->id;
            $withdraw->save();

            return $ledger;
        });

        return $ledger;
    }

    public function sendNotification()
    {
        DB::transaction(function(){
            $affiliates = config('commission.member-types.affiliates');

            $ledgers = LedgerModel::with('user')
                ->where('amount', '>', 0)
                ->where('is_notified', 0)
                ->where('type', 'commission')
                ->whereRaw("EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = cm_ledger.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))")
                ->lockForUpdate()
                ->get();

            foreach ($ledgers as $ledger) {

                $user = $ledger->user;

                // Mail::to($user)->send(new LedgerAdded($ledger, $user));
                \Commissions\Mail::send(
                    $user->email,
                    "Funds Added",
                    view('emails.ledger.added', compact('user', 'ledger'))->render()
                );

                $ledger->is_notified = 1;
                $ledger->save();
            }
        });
    }

}