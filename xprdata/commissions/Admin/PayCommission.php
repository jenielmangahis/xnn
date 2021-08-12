<?php


namespace Commissions\Admin;

use Commissions\CsvReport;
use Commissions\Exceptions\AlertException;
use Illuminate\Support\Facades\DB;
use App\User;
use App\PaymentHistory;
use App\PaymentDetails;
use App\Payment;
use App\Payout;
use Commissions\Contracts\PaymentInterface;

class PayCommission
{
    const DEBUG = false;
    const IS_LOCKED = 1; // set to 0 for testing
    const MINIMUM_AMOUNT_TRANSFER = 0;
    const UPDATE_IS_PAID = 1;

    protected $pay_quicker;
    protected $payment;

    public function __construct(PaymentInterface $payment)
    {
        $this->payment = $payment;

    }

    public function getLockedPeriods($commission_type_ids)
    {
        $periods = DB::table('cm_commission_periods AS p')
            ->join('cm_commission_types AS t', 't.id', '=', 'p.commission_type_id')
            ->where('p.is_locked', self::IS_LOCKED)
            ->whereExists(function($query)
            {
                $query->select(DB::raw(1))
                    ->from('cm_commission_payouts AS cp')
                    ->whereRaw('cp.commission_period_id = p.id AND cp.is_paid = 0 AND cp.is_rollover = 0');
            })
            ->whereRaw('FIND_IN_SET(t.id,?)', [$commission_type_ids])
            ->selectRaw("
                p.id,
	            CONCAT('[', p.id, ']-', t.`name`, ' (', DATE_FORMAT(p.start_date, '%Y-%m-%d'), ' to ', DATE_FORMAT(p.end_date, '%Y-%m-%d'), ')') `text`
            ")
            ->get();

        return $periods;
    }

    public function getPayouts($commission_period_ids)
    {
        $username = $this->payment->getUsername();
        $email = $this->payment->getEmail();
        $payouts = $this->getQuery()
            ->groupBy('u.id')
            ->groupBy("p.currency")
            ->having('amount', '>', 0)
            // ->whereRaw('FIND_IN_SET(pr.id, ?)' , [$commission_period_ids])
            /*->where(function($query) use ($commission_period_ids){
                $query->whereRaw('
                    (
		                FIND_IN_SET(pr.id, ?) 
		                OR (p.is_rollover = 1 
		                    AND EXISTS(
		                        SELECT 1 
		                        FROM cm_commission_periods 
		                        WHERE FIND_IN_SET(id, ?) 
		                        AND commission_type_id = pr.commission_type_id
		                    )
		                )
		            )', [$commission_period_ids, $commission_period_ids]);

            })*/
            ->selectRaw("
                u.id AS user_id,
                u.fname AS first_name,
                u.lname AS last_name,
                CONCAT(u.id, ': ', u.fname, ' ', u.lname) `name`,
                
                GROUP_CONCAT(p.id ORDER BY p.id) AS ids,
                GROUP_CONCAT(DISTINCT t.`name` ORDER BY t.`name` ASC) AS commission_type,
                SUM(IF(p.is_rollover = 1, p.amount, 0)) rollover,
                SUM(IF(p.is_rollover = 0, p.amount, 0)) payout,
                SUM(p.amount) amount,
                p.currency,
                $username AS username,
                $email,
                
                NULL is_selected
            ")
            ->get()->toArray();

        if(count($payouts) == 0)
        {
            return ['has_report' => false, 'link' => null, 'payouts' => []];
        }


        $payouts = json_decode(json_encode($payouts), true);

        $data = [];

        foreach ($payouts as $payout) {
            $data[] = [
                'user_id' => $payout['user_id'],
                'first_name' => $payout['first_name'],
                'last_name' => $payout['last_name'],
                'username' => $payout['username'] !== null ? $payout['username'] : "NO ACCOUNT",
                'email' => $payout['email'],
                'amount' => $payout['amount'],
                'currency' => $payout['currency'],
            ];
        }

        $csv_report = new CsvReport("csv/admin/pay_commission");

        $link = $csv_report->generateLink(
            "pay_" . date('Y_m_d_H_i_s'),
            $data,
            [
                'user_id',
                'first_name',
                'last_name',
                'username',
                'email',
                'amount',
            ]
        );

        return ['payouts' => $payouts, 'has_report' => true, 'link' => $link];
    }

    public function getHistory($request)
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
                h.download_links,
                NULL `action`
            ");

        // count total records
        $recordsTotal = $query->count(DB::raw("1"));

        // apply where
        if(isset($search) && $search['value'] != '') {
            $value = $search['value'];
            $query =
                $query->where(function($query) use ($value) {
                    $query->where('h.created_at', 'LIKE', "%{$value}%")
                        ->orWhereRaw("CONCAT(p.fname, ' ', p.lname) LIKE ?", ["%{$value}%"]);
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

        for($i =0; $i < count($data); $i++) {

            $data[$i]->download_links = empty($data[$i]->download_links) ? [] : json_decode($data[$i]->download_links, true);
        }

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getQuery()
    {
        $query = DB::table('cm_commission_payouts AS p')
            ->join('cm_commission_periods AS pr', 'pr.id', '=', 'p.commission_period_id')
            ->join('cm_commission_types AS t', 't.id', '=', 'pr.commission_type_id')
            ->join('users AS u', 'u.id', '=', 'p.payee_id')
            ->leftJoin($this->payment->getTable(), $this->payment->getUserId(), '=', 'u.id')
            ->where('pr.is_locked', self::IS_LOCKED)
            ->where('p.is_paid', 0)
            ->where('t.payout_type', 'cash')
            ->where('t.is_active', 1);

        return $query;
    }

    public function getTotal($ids)
    {
        $currencies = $this->getQuery()
            ->whereRaw('FIND_IN_SET(p.id, ?)', [$ids])
            ->selectRaw("
                p.currency,
                SUM(p.amount) amount
            ")
            ->groupBy("p.currency")
            ->get()
        ;

        $total = "";

        foreach($currencies as $key => $currency) {
            if($key) {
                $total .= " ";
            }
            $amount = number_format($currency->amount, 2, '.', ',');
            $total .= "{$currency->currency}: $amount";
        }

        return ['total' => $total];
    }

    public function start($payout_ids, $prepared_by_id, $period_ids)
    {
        $user = User::find($prepared_by_id);

        if($user == null) throw new AlertException("No login user");

        $history = PaymentHistory::running()->latest()->first();

        if($history != null)
        {
            throw new AlertException(
                "An instance of payment is currently running",
                AlertException::TYPE_WARNING,
                $history);
        }

        $history = DB::transaction(function() use ($payout_ids, $prepared_by_id, $period_ids){
            $this->ensureLockedAndUnpaid($payout_ids);

            $payouts = $this->getQuery()
                ->selectRaw("
                    GROUP_CONCAT(p.id ORDER BY p.id) AS ids,
                    CONCAT(u.id, ': ', u.fname, ' ', u.lname) `name`,
                    u.id AS user_id,
                    t.`name` commission_type,
                    SUM(p.amount) amount
                ")
                ->groupBy('u.id')
                ->groupBy("p.currency")
                ->having('amount', '>', 0)
                ->whereRaw('FIND_IN_SET(p.id, ?)', [$payout_ids])
                ->lockForUpdate()
                ->get();

            $history = new PaymentHistory();
            $history->status = PaymentHistory::STATUS_PENDING;
            $history->prepared_by_id = $prepared_by_id;
            $history->payout_ids = $payout_ids;
            $history->period_ids = "ALL";
            $history->pay_count = (count($payouts) * 3) + 2;
            $history->save();

            $this->logger($history->id, "Process started");
            $this->logger($history->id,"          ", false);

            return $history;
        });

        return $history;
    }

    public function log($history_id, $seek)
    {
        $history = PaymentHistory::findOrFail($history_id);

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

        $user = User::find($history->prepared_by_id);

        if($user != null)
        {
            $history->prepared_by = $user->fname . ' ' . $user->lname;
        }

        return ['seek' => 0, 'lines' => [], 'history' => $history];
    }

    public function pay($payout_ids, $prepared_by_id, $period_ids, $history_id)
    {
        $history = PaymentHistory::find($history_id);

        if($history == null || !$history->isPending()) throw new AlertException("Action not allowed");

        $history->status = PaymentHistory::STATUS_RUNNING;
        $history->save();

        $history = DB::transaction(function() use ($payout_ids, $prepared_by_id, $period_ids, $history) {

            $this->logger($history->id, "Running...");
            $this->payment->onBeforePayment($history);

            $success_payout_ids = [];
            try
            {
                $this->ensureLockedAndUnpaid($payout_ids);

                /*if($payout_ids != $history->payout_ids || $period_ids != $history->period_ids)
                {
                    // basig ma wrong sa javascript na side
                    throw new AlertException("Payouts not matched");
                }*/

                $username = $this->payment->getUsername();

                $columns = "
                    GROUP_CONCAT(p.id ORDER BY p.id) AS ids,
                    CONCAT(u.lname, ', ', u.fname) `name`,
                    u.id AS user_id,
                    t.`name` commission_type,
                    SUM(p.amount) amount,
                    p.currency,
                    $username AS username
                ";

                $fields = $this->payment->getFields();

                if(count($fields) > 0) {
                    $columns .= "," . implode(",", $fields);
                }

                $payouts = $this->getQuery()
                    ->selectRaw($columns)
                    ->groupBy('u.id')
                    ->groupBy("p.currency")
                    ->having('amount', '>', 0)
                    ->whereRaw('FIND_IN_SET(p.id, ?)', [$payout_ids])
                    ->lockForUpdate()
                    ->get();

                foreach($payouts as $payout)
                {
                    if(+$payout->amount < self::MINIMUM_AMOUNT_TRANSFER) continue;
                    if ($payout->username === null) continue;

                    $payment = new Payment();
                    $payment->user_id = $payout->user_id;
                    $payment->amount = $payout->amount;
                    $payment->status = Payment::STATUS_PROCESSING;
                    $payment = $history->payments()->save($payment);

                    $user_payout_ids = explode(',', $payout->ids);
                    $this->logger($history->id,"          ", false);
                    $this->logger($history->id, "Processing the \${$payout->amount} payment for {$payout->name}");

                    $payment = $this->payment->sentPayment($payment, $payout);

                    if($payment->status !== Payment::STATUS_SUCCESS) {
                        $this->logger($history->id, "$payment->status: $payment->message");
                        // $this->logger($history->id, $payment->response);
                        $this->logger($history->id,"          ", false);
                        continue;
                    }

                    if(!!$payment->message) {
                        $this->logger($history->id, "SUCCESS: $payment->message");
                    }

                    $this->logger($history->id,"The payment for {$payout->name} is processed successfully. Transaction No. {$payment->transaction_no}");

                    $success_payout_ids = array_merge($success_payout_ids, $user_payout_ids);

                    if($this->payment->isSetPaidPerPayment()) {
                        if(self::DEBUG) $this->logger($history->id,"Marking as paid: " . implode(",", $user_payout_ids));

                        Payout::whereIn('id', $user_payout_ids)->update([
                            'is_paid' => static::UPDATE_IS_PAID
                        ]);

                        if(self::DEBUG) $this->logger($history->id, "Done marking");
                    }

                    $this->logger($history->id,"          ", false);

                    foreach($user_payout_ids as $payout_id)
                    {
                        $details = new PaymentDetails();
                        $details->payout_id = $payout_id;
                        $payment->details()->save($details);
                    }

                    $this->logger($history->id,"          ", false);
                }

                $this->payment->onAfterPayment($history);

                if(!$this->payment->isSetPaidPerPayment()) {

                    $this->logger($history->id,"Set is paid (ALL)." );
                    if(self::DEBUG) $this->logger($history->id,"Marking as paid: " . implode(",", $success_payout_ids));

                    Payout::whereIn('id', $success_payout_ids)->update([
                        'is_paid' => static::UPDATE_IS_PAID
                    ]);
                }

                $this->setToRollover($period_ids);

                $this->logger($history->id,"          ", false);

            }
            catch(\Exception $main)
            {
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

    private function ensureLockedAndUnpaid($ids)
    {
        $count = $this->getQuery()
            ->whereRaw('FIND_IN_SET(p.id, ?)', [$ids])
            ->lockForUpdate()
            ->count(DB::raw("1"));

        if($count != count(explode(",", $ids)))
        {
            throw new AlertException("Not the same count");
        }
    }

    public function getPaymentDetails($request)
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

        if(!$history_id)
        {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
        }

        $username = $this->payment->getUsername();

        // build the query
        $query = DB::table('cm_payments AS p')
            ->leftJoin('users AS u', 'u.id', '=', 'p.user_id')
            ->leftJoin($this->payment->getTable(), $this->payment->getUserId(), '=', 'u.id')
            ->selectRaw("
                p.id AS payment_id,
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
        if(isset($search) && $search['value'] != '')
        {
            $value = $search['value'];
            $query =
                $query->where(function($query) use ($value, $username) {
                    $query->where('p.transaction_no','LIKE', "%{$value}%")
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
        if(isset($order) && count($order))
        {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // apply limit
        $query = $query->take($take);

        // apply offset
        if($skip) $query = $query->skip($skip);

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function setToRollover($commission_period_ids)
    {

        if(self::MINIMUM_AMOUNT_TRANSFER <= 0)
        {
            return;
        }

        $rollovers = $this->getQuery()
            ->selectRaw("
                GROUP_CONCAT(p.id ORDER BY p.id) AS ids,
                u.id AS user_id,
                SUM(p.amount) amount
            ")
            ->groupBy('u.id')
            ->groupBy("p.currency")
            ->having('amount', '<', self::MINIMUM_AMOUNT_TRANSFER)
            ->where(function($query) use ($commission_period_ids){
                $query->whereRaw('
                    (
		                FIND_IN_SET(pr.id, ?) 
		                OR (p.is_rollover = 1 
		                    AND EXISTS(
		                        SELECT 1 
		                        FROM cm_commission_periods 
		                        WHERE FIND_IN_SET(id, ?) 
		                        AND commission_type_id = pr.commission_type_id
		                    )
		                )
		            )', [$commission_period_ids, $commission_period_ids]);

            })->get();

        $payout_ids = [];

        foreach($rollovers as $rollover)
        {
            $payout_ids[] = $rollover['ids'];
        }

        if(count($payout_ids) > 0)
        {
            $ids = implode(',', $payout_ids);

            // $this->log->debug('Rollover:' . $ids);

            Payout::whereRaw('FIND_IN_SET(id, ?)', [$ids])->update([
                'is_rollover' => 1
            ]);

        }

    }

    protected function logger($history_id, $message, $time = true)
    {
        $file = storage_path("logs/pay/{$history_id}.log");

        try
        {
            if(!file_exists($file))
            {
                file_put_contents($file, " ", FILE_APPEND);
                chmod($file, 0777);
            }

            if($time)
            {
                $message = "[" . date("Y-m-d H:m:i") . "] " . $message;
            }

            file_put_contents($file, $message . PHP_EOL, FILE_APPEND);
        }
        catch (\Exception $ex)
        {
            // do nothing
        }
    }

    public function markAsPaid($ids, $user_id)
    {
        $result = DB::transaction(function() use ($ids, $user_id) {

            $this->ensureLockedAndUnpaid($ids);
            $result = Payout::whereRaw('FIND_IN_SET(id, ?)', [$ids])->update([
                'is_paid' => static::UPDATE_IS_PAID,
                'mark_as_paid_by_id' => $user_id
            ]);

            // throw new AlertException($ids);

            return $result;
        });

        return ['succeeded' => 1, 'data' => $result];
    }
}