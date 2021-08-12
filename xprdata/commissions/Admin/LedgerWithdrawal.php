<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 11/29/2019
 * Time: 9:34 AM
 */

namespace Commissions\Admin;

use App\PaymentHistory;
use App\Payment;
use App\PaymentDetails;
use App\LedgerWithdrawal as LW;
use App\User;
use App\Ledger;
use Commissions\Contracts\PaymentInterface;
use Commissions\Exceptions\AlertException;
use Illuminate\Support\Facades\DB;

class LedgerWithdrawal
{
    const DEBUG = true;
    const MINIMUM_AMOUNT_TRANSFER = 0;

    protected $payment;

    public function __construct(PaymentInterface $payment)
    {
        $this->payment = $payment;

    }

    /*public function __construct()
    {
        if (self::DEBUG) {
            $this->hyperwallet = new Hyperwallet(
                "restapiuser@25398401619",
                'P@$sw0rd123.',
                "prg-cfa16176-7872-4b4d-934b-90086fa358f2"
            );

        }
    }*/

    public function getPendingRequest($start_date = null, $end_date = null) // done
    {
        $query = $this->getQuery();

        if (!!$start_date && !!$end_date) {
            $query->whereRaw('DATE(w.created_at) BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        return $query->get();
    }

    protected function getQuery() // done
    {
        $username = $this->payment->getUsername();

        $columns = "
            w.id AS ids,
            w.ledger_id,
            w.created_at AS `date`,
            w.user_id,
            CONCAT(u.fname, ' ', u.lname) `name`,
            u.site,
            w.amount,
            $username AS username
        ";

        $fields = $this->payment->getFields();

        if (count($fields) > 0) {
            $columns .= "," . implode(",", $fields);
        }

        $query = DB::table('cm_ledger_withdrawal AS w')
            ->selectRaw($columns)
            ->join('users AS u', 'u.id', '=', 'w.user_id')
            ->leftJoin($this->payment->getTable(), $this->payment->getUserId(), '=', 'u.id')
            ->where('w.status', LW::STATUS_PENDING);

        return $query;
    }

    public function startProcess($ids, $prepared_by_id) // done
    {
        $history = PaymentHistory::running()->latest()->first();

        if ($history != null) {
            throw new AlertException(
                "An instance of payment is currently running. Please view the running payment in the history tab.",
                $history,
                AlertException::TYPE_WARNING
            );
        }

        $history = DB::transaction(function () use ($ids, $prepared_by_id) {

            $comma_delimited_ids = implode(",", $ids);

            $count = $this->ensureRequestsArePending($ids);

            $history = new PaymentHistory();
            $history->status = PaymentHistory::STATUS_PENDING;
            $history->prepared_by_id = $prepared_by_id;
            $history->withdrawal_ids = $comma_delimited_ids;
            $history->period_ids = $comma_delimited_ids;
            $history->pay_count = ($count * 3) + 2;
            $history->save();

            $this->logger($history->id, "Process started");
            $this->logger($history->id, "          ", false);

            return $history;
        });

        $user = User::find($prepared_by_id);

        if ($user !== null) {
            $history->prepared_by = $user->fname . ' ' . $user->lname;
        }

        return $history;
    }

    public function pay($history_id)
    {
        $history = PaymentHistory::find($history_id);

        if ($history == null || !$history->isPending()) throw new AlertException("Action not allowed");

        $history->status = PaymentHistory::STATUS_RUNNING;
        $history->save();

        $history = DB::transaction(function () use ($history) {

            $this->logger($history->id, "Running...");

            try {
                $ids = explode(",", $history->withdrawal_ids);
                $this->ensureRequestsArePending($ids);

                $requests = $this->getQuery()
                    ->whereIn("w.id", $ids)
                    ->lockForUpdate()
                    ->get();

                foreach ($requests as $request) {
                    if (+$request->amount < self::MINIMUM_AMOUNT_TRANSFER) continue;
                    if ($request->username === null) continue;

                    $payment = new Payment();
                    $payment->user_id = $request->user_id;
                    $payment->amount = $request->amount;
                    $payment->status = 'PROCESSING'; // SUCCESS // FAILED
                    $payment = $history->payments()->save($payment);

                    $withdrawal_ids = explode(',', $request->ids);
                    $this->logger($history->id, "          ", false);
                    $this->logger($history->id, "Sending \${$request->amount} payment to {$request->name}");

                    $payment = $this->payment->sentPayment($payment, $request);

                    if ($payment->status !== Payment::STATUS_SUCCESS) {
                        $this->logger($history->id, "FAILED: $payment->message");
                        // $this->logger($history->id, $payment->response);
                        $this->logger($history->id, "          ", false);
                        continue;
                    }

                    $this->logger($history->id, "SUCCESS: $payment->message");
                    $this->logger($history->id, "Successfully sent to {$request->name}. Transaction No. {$payment->transaction_no}");

                    LW::whereIn('id', $withdrawal_ids)->update([
                        'status' => LW::STATUS_PAID
                    ]);

                    $this->logger($history->id, "          ", false);

                    foreach ($withdrawal_ids as $withdrawal_id) {
                        $details = new PaymentDetails();
                        $details->withdrawal_id = $withdrawal_id;
                        $payment->details()->save($details);
                    }

                    $this->logger($history->id, "          ", false);
                }

                // Set rollover here

                $this->logger($history->id, "          ", false);

            } catch (\Exception $main) {
                $history->error = json_encode([
                    'message' => $main->getMessage(),
                    'trace' => $main->getTraceAsString(),
                ]);

                $history->status = PaymentHistory::STATUS_HAS_ERROR;
                $history->save();

                $this->logger($history->id, $main->getMessage());
            }

            $history->status = PaymentHistory::STATUS_COMPLETED;
            $history->save();

            return $history;
        });

        $this->logger($history->id, "Process ended");

        return $history;
    }

    protected function logger($history_id, $message, $time = true) // done
    {
        $file = storage_path("logs/pay/{$history_id}.log");

        try {
            if (!file_exists($file)) {
                file_put_contents($file, " ", FILE_APPEND);
                chmod($file, 0777);
            }

            if ($time) {
                $message = "[" . date("Y-m-d H:m:i") . "] " . $message;
            }

            file_put_contents($file, $message . PHP_EOL, FILE_APPEND);
        } catch (\Exception $ex) {
            // do nothing
        }
    }

    protected function ensureRequestsArePending($ids) // done
    {
        $count = $this->getQuery()
            ->whereIn('w.id', $ids)
            ->lockForUpdate()
            ->count(DB::raw("1"));

        if ($count != count($ids)) {
            throw new AlertException("There are requests that are already been processed. Please reload the page.");
        }

        return $count;
    }

    public function getHistoryLog($history_id, $seek) // done
    {
        $history = PaymentHistory::findOrFail($history_id);

        $user = User::find($history->prepared_by_id);

        if ($user !== null) {
            $history->prepared_by = $user->fname . ' ' . $user->lname;
        }

        if ($seek != null) {

            $lines = [];
            $handle = fopen(storage_path("logs/pay/{$history_id}.log"), 'rb');

            if ($seek > 0) {
                fseek($handle, $seek);
            }

            while (($line = fgets($handle, 4096)) !== false) {
                $lines[] = $line;
            }
            $seek = ftell($handle);

            return ['seek' => $seek, 'lines' => $lines, 'history' => $history];
        }

        return ['seek' => 0, 'lines' => [], 'history' => $history];
    }

    public function getHistory($request) // done
    {
        $draw = intval($request['draw']);
        $skip = $request['start'];
        $take = $request['length'];
        $search = $request['search'];
        $order = $request['order'];
        $columns = $request['columns'];

        // build the query
        $query = DB::table('cm_payment_history AS h')
            ->leftJoin('users AS p', 'p.id', '=', 'h.prepared_by_id')
            ->selectRaw("
                h.id,
                CONCAT(p.fname, ' ', p.lname) prepared_by,
                h.created_at,
                h.status,
                NULL `action`
            ");

        // count total records
        $recordsTotal = $query->count(DB::raw("1"));

        // apply where
        if (isset($search) && $search['value'] != '') {
            $value = $search['value'];
            $query =
                $query->where(function ($query) use ($value) {
                    $query->where('h.created_at', 'LIKE', "%{$value}%")
                        ->orWhereRaw("CONCAT(p.fname, ' ', p.lname) LIKE ?", ["%{$value}%"]);
                });
        }

        // count total filtered records
        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by (only 1 column for now)
        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // apply limit
        $query = $query->take($take);

        // apply offset
        if ($skip) $query = $query->skip($skip);

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getPaymentDetails($request) // done
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
        $history_id = $request['history_id'];

        if (!$history_id) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $username = $this->payment->getUsername();

        // build the query
        $query = DB::table('cm_payments AS p')
            ->leftJoin('users AS u', 'u.id', '=', 'p.user_id')
            ->leftJoin($this->payment->getTable(), $this->payment->getUserId(), '=', 'u.id')
            ->selectRaw("
                p.id AS accounting_id,
                p.transaction_no AS reference_no,
                CONCAT(u.id, ': ', u.fname, ' ', u.lname) AS member,
                $username AS username,
                p.amount,
                p.`status`
            ")
            ->where('p.history_id', $history_id);

        // count total records
        $recordsTotal = $query->count(DB::raw("1"));

        // apply where
        if (isset($search) && $search['value'] != '') {
            $value = $search['value'];
            $query =
                $query->where(function ($query) use ($value, $username) {
                    $query->where('p.transaction_no', 'LIKE', "%{$value}%")
                        ->orWhere('p.id', 'LIKE', "%{$value}%")
                        ->orWhere('p.amount', 'LIKE', "%{$value}%")
                        ->orWhere($username, 'LIKE', "%{$value}%")
                        ->orWhere('p.status', 'LIKE', "%{$value}%")
                        ->orWhereRaw("CONCAT(u.id, ': ', u.fname, ' ', u.lname) LIKE ?", ["%{$value}%"]);
                });
        }

        // count total filtered records
        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by (only 1 column for now)
        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // apply limit
        $query = $query->take($take);

        // apply offset
        if ($skip) $query = $query->skip($skip);

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function rejectRequest($comma_delimited_ids, $rejected_by_id) // done
    {
        return DB::transaction(function () use ($comma_delimited_ids, $rejected_by_id) {
            $ids = explode(",", $comma_delimited_ids);

            $count = $this->ensureRequestsArePending($ids);

            $withdrawal_request = LW::whereIn('id', $ids)->lockForUpdate()->get();

            foreach ($withdrawal_request as $request) {
                $ledger = new Ledger();
                $ledger->user_id = $request->user_id;
                $ledger->amount = +$request->amount;
                $ledger->notes = "Withdrawal Request Rejected";
                $ledger->type = Ledger::TYPE_WITHDRAWAL_REJECTED;
                $ledger->reference_number = $request->id;
                $ledger->save();

                $request->status = LW::STATUS_REJECTED;
                $request->updated_by_id = $rejected_by_id;
                $request->save();
            }

            return $withdrawal_request;
        });
    }

}