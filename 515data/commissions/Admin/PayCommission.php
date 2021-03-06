<?php


namespace Commissions\Admin;

use Carbon\Carbon;
use Commissions\CsvReport;
use Commissions\Exceptions\AlertException;
use Illuminate\Support\Facades\DB;
use App\User;
use App\PaymentHistory;
use App\PaymentDetails;
use App\Payment;
use App\Payout;
use Commissions\Contracts\PaymentInterface;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;

class PayCommission
{
    const DEBUG = false;
    const IS_LOCKED = 1; // set to 0 for testing
    const MINIMUM_AMOUNT_TRANSFER = 0;
    const UPDATE_IS_PAID = 1; // TODO:
    const TECH_FEE = 12.20;

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
                'account_no' => $payout['username'] !== null ? $payout['username'] : "NO ACCOUNT",
                'email' => $payout['email'],
                'amount' => $payout['amount'],
                'currency' => $payout['currency'],
            ];
        }

        $csv_report = new CsvReport("csv/admin/pay_commission");
        $link = null;
        /*$link = $csv_report->generateLink(
            "pay_" . date('Y_m_d_H_i_s'),
            $data,
            [
                'user_id',
                'first_name',
                'last_name',
                'account_no',
                'email',
                'amount',
                'currency',
            ]
        );*/

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
                h.csv_file,
                h.csv_file_upload,
                h.receipt_num,
                NULL `action`
            ")
            ->where("h.status", "<>" , PaymentHistory::STATUS_PENDING_UPLOAD);

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

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getPending($request)
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
                h.csv_file,
                h.csv_file_upload,
                h.receipt_num,
                NULL `action`
            ")
            ->where("h.status", PaymentHistory::STATUS_PENDING_UPLOAD);

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

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data');
    }

    public function getQuery()
    {
        $query = DB::table('cm_commission_payouts AS p')
            ->join('cm_commission_periods AS pr', 'pr.id', '=', 'p.commission_period_id')
            ->join('cm_commission_types AS t', 't.id', '=', 'pr.commission_type_id')
            ->join('users AS u', 'u.id', '=', 'p.payee_id')
            ->join("cm_affiliates AS a", "a.user_id", "=", "u.id")
            ->leftJoin("billing AS b", "b.userid", "=", "u.id")
            ->leftJoin($this->payment->getTable(), $this->payment->getUserId(), '=', 'u.id')
            ->leftJoin("cm_ledger_payout AS lp", "lp.payout_id", "=", "p.id")
            ->where('pr.is_locked', self::IS_LOCKED)
            ->where('p.is_paid', 0)
            ->where('t.payout_type', 'cash')
            ->where('t.is_active', 1);

        return $query;
    }

    public function getTotal($ids)
    {
        $currencies = $this->getQuery()
            ->whereRaw("u.id IN ($ids)")
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

    public function start($user_ids, $prepared_by_id, $period_ids)
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

        $history = DB::transaction(function() use ($user_ids, $prepared_by_id, $period_ids){

            // $this->ensureLockedAndUnpaid($user_ids);

            $payouts = $this->getQuery()
                ->selectRaw("
                    GROUP_CONCAT(p.id ORDER BY p.id) AS ids,
                    SUM(p.amount) amount
                ")
                ->groupBy('u.id')
                ->groupBy("p.currency")
                ->having('amount', '>', 0)
                ->whereRaw("u.id  IN ($user_ids)")
                ->lockForUpdate()
                ->get();

            $payout_ids = '';
            foreach($payouts as $payout) {
                $payout_ids .= $payout->ids.",";
            }

            $payout_ids = substr($payout_ids, 0, -1);

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

    public function pay($user_ids, $prepared_by_id, $period_ids, $history_id)
    {
        $history = PaymentHistory::find($history_id);

        if($history == null || !$history->isPending()) throw new AlertException("Action not allowed");

        $history->status = PaymentHistory::STATUS_RUNNING;
        $history->save();

        $history = DB::transaction(function() use ($user_ids, $prepared_by_id, $period_ids, $history) {

            $this->logger($history->id, "Running...");
            $this->payment->onBeforePayment($history);

            $success_payout_ids = [];
            try
            {
                // $this->ensureLockedAndUnpaid($user_ids);

                /*if($payout_ids != $history->payout_ids || $period_ids != $history->period_ids)
                {
                    // basig ma wrong sa javascript na side
                    throw new AlertException("Payouts not matched");
                }*/

                $username = $this->payment->getUsername();

                $end_date = Carbon::now()->format("Y-m-d");
                $start_date = Carbon::now()->startOfMonth()->format("Y-m-d");

                $columns = "
                    GROUP_CONCAT(p.id ORDER BY p.id) AS ids,
                    CONCAT(u.lname, ', ', u.fname) `name`,
                    u.id AS user_id,
                    t.`name` commission_type,
                    SUM(p.amount) amount,
                    p.currency,
                    a.affiliated_date,
                    b.billdate,
                    b.nochargeuntil,
                    EXISTS(
                        SELECT 1 FROM v_cm_transactions t
                        WHERE t.`type` = 'sub' 
                            AND t.user_id = a.user_id 
                            AND t.transaction_date BETWEEN '$start_date' AND '$end_date'
                    ) is_already_billed_this_month,
                    GROUP_CONCAT(DISTINCT lp.ledger_id) ledger_ids,
                    (
                        SELECT
                            r.name
                        FROM cm_daily_ranks dr
                        JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
                        WHERE dr.user_id = u.id
                            AND dr.rank_date = pr.end_date
                            AND pr.commission_type_id <> 7
                        ORDER BY pr.end_date DESC
                        LIMIT 1
                    ) paid_as_rank,
                    SUM(IF(pr.commission_type_id = 1, p.amount, 0)) gross_weekly_immediate_earnings,
                    SUM(IF(pr.commission_type_id = 2, p.amount, 0)) gross_monthly_earnings_true_up,
                    SUM(IF(pr.commission_type_id = 4, p.amount, 0)) gross_monthly_residual_personal_energy_account,
                    SUM(IF(pr.commission_type_id = 3, p.amount, 0)) gross_unilevel_residual,
                    SUM(IF(pr.commission_type_id = 5, p.amount, 0)) gross_generation_residual,
                    SUM(IF(pr.commission_type_id = 7, p.amount, 0)) other_income,
                    SUM(p.amount) total_gross,
                    IFNULL((
                        SELECT SUM(_p.amount)
                        FROM cm_commission_payouts _p
                        JOIN cm_commission_periods _pr ON _pr.id = _p.commission_period_id AND _pr.is_locked = 1
                        WHERE _p.payee_id = u.id AND _pr.end_date BETWEEN DATE(CONCAT(YEAR(MAX(pr.end_date)),'-01-01')) AND DATE(CONCAT(YEAR(MAX(pr.end_date)),'-12-31'))
                    ), 0) year_to_date_gross,
                    IF(
                        u.piva IS NULL 
                        AND IFNULL((
                            SELECT SUM(_p.amount)
                            FROM cm_commission_payouts _p
                            JOIN cm_commission_periods _pr ON _pr.id = _p.commission_period_id AND _pr.is_locked = 1
                            WHERE _p.payee_id = u.id), 0)
                        >= 6200
                    ,'Yes','No') AS has_reached_6200_and_has_no_piva,
                    GROUP_CONCAT(DISTINCT CONCAT(DATE_FORMAT(pr.start_date,'%d/%m/%Y'), '-', DATE_FORMAT(pr.end_date,'%d/%m/%Y')) ORDER BY pr.end_date) period_of_reference,
                    
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
                    ->whereRaw("u.id IN ($user_ids)")
                    ->lockForUpdate()
                    ->get();

                foreach($payouts as $payout)
                {
                    if(+$payout->amount < self::MINIMUM_AMOUNT_TRANSFER) continue;
                    if ($payout->username === null) continue;

                    // if(+$payout->is_2_months_after_enrollment && $payout->amount < static::TECH_FEE) continue;

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

                    foreach(array_chunk($success_payout_ids, 1000) as $spi)
                    {
                        Payout::whereIn('id', $spi)->update([
                        'is_paid' => static::UPDATE_IS_PAID
                    ]);
                }
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

            $history->status = PaymentHistory::STATUS_PENDING_UPLOAD;
            $history->save();

            return $history;
        });

        $this->logger($history->id, "Process ended");

        return $history;
    }

    private function ensureLockedAndUnpaid($ids)
    {
        $count = $this->getQuery()
            ->whereRaw("u.id IN ($ids)")
            ->lockForUpdate()
            ->count(DB::raw("1"));
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
                p.receipt_num AS reference_no,
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
                        ->orWhere('p.receipt_num', 'LIKE', "%{$value}%")
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
            
            $payouts = $this->getQuery()
                ->selectRaw("
                    GROUP_CONCAT(p.id ORDER BY p.id) AS ids,
                    SUM(p.amount) amount
                ")
                ->groupBy('u.id')
                ->groupBy("p.currency")
                ->having('amount', '>', 0)
                ->whereRaw("u.id  IN ($ids)")
                ->lockForUpdate()
                ->get();

            $payout_ids = '';
            
            foreach($payouts as $payout) {
                $payout_ids .= $payout->ids.",";
            }

			$ids = explode(',', $payout_ids);
            
            foreach(array_chunk($ids, 1000) as $c)
            {
                $result = Payout::whereIn('id', $c)->update([
                    'is_paid' => static::UPDATE_IS_PAID,
                    'mark_as_paid_by_id' => $user_id
                ]);
            }

            // throw new AlertException($ids);

            return $result;
        });

        return ['succeeded' => 1, 'data' => $result];
    }

    public function getCsvFile($id)
    {

    }

    public function uploadCsv(PaymentHistory $history, \Illuminate\Http\UploadedFile $file)
    {
        
        if($history->status !== PaymentHistory::STATUS_PENDING_UPLOAD) {
            throw new AlertException("Payout file was already been processed. (Status: $history->status)");
        }

        if(strtoupper($file->getClientOriginalExtension()) !== "CSV") {
            throw new AlertException("Payout file must be a CSV file.");
        }

        $history = DB::transaction(function() use ($history, $file) {
            try {
                $reader = Reader::createFromPath($file->getRealPath(), 'r') ;
            } catch (\Exception $ex) {
                throw new AlertException("Unable to read the payout file.");
            }
            
            $results = $reader->fetchAssoc();
            
            $num = Payment::where("year", $history->year)->lockForUpdate()->max('num');

            foreach ($results as $row) {
                $payment = $history->payments()->find($row["Payment ID"]);

                if($payment === null) continue;

                /*$payment->cf = $row["C.F."];
                $payment->piva = $row["P.IVA"];
                $payment->codice_sdi = $row["CODICE SDI"];
                $payment->or_pec = $row["OR PEC"];
                $payment->iban = $row["IBAN"];
                $payment->paid_as_rank = $row["Paid as Title"];
                $payment->gross_weekly_immediate_earnings = $row["Gross amount for commission Weekly Immediate earnings"];
                $payment->gross_monthly_earnings_true_up = $row["Gross amount for commission Monthly earnings True-up"];
                $payment->gross_monthly_residual_personal_energy_account = $row["Gross amount for commission Monthly Residual on Personal Energy Account"];
                $payment->gross_unilevel_residual = $row["Gross amount for commission Unilevel Residual"];
                $payment->gross_generation_residual = $row["Gross amount for commission Generational Residual"];
                $payment->other_income = $row["Other Income"];
                $payment->total_gross = $row["Total gross commission amount to be paid"];
                $payment->year_to_date_gross = $row["Year To Date Gross"];
                $payment->has_reached_6200_and_has_no_piva = $row["Has reached ???6200 and has no P.IVA"];
                $payment->period_of_reference = $row["Period of Reference"];*/
                $payment->charged_by_card = $row["Charged By Card"];
                $payment->techonology_fee_to_subtract = $row["Technology Fee to Subtract"];
                $payment->taxes_ritenuta_irpef = $row["Taxes Ritenuta IRPEF (Income Tax)"];
                $payment->taxes_vat = $row["Taxes VAT (P.IVA)"];
                $payment->taxes_trattenuta_previd = $row["Taxes Trattenuta Previd. INPS (social security tax)"];
                $payment->total_net_amount = $row["Total net amount"];
                $payment->actual_date = $row["Actual Date when the bank sent the money to the Associate"];
                $payment->batch_number = $row["Batch Number from Plank to SDI"];
                $payment->bank_transaction_code = $row["Bank Transaction Code"];
                // $payment->receipt_num = $row["Receipt Num"];
                $payment->is_processed = 1;
                $num++;
                $payment->num = $num;
                $payment->receipt_num = str_pad($payment->num, 4, "0", STR_PAD_LEFT) . "/" . $history->year;

                $payment->save();
            }

            $missing = $history->payments()->where("is_processed", 0)->where("status", "SUCCESS")->count();

            if($missing > 0) {
                throw new AlertException("Unable to process. Payment IDs does not match.");
            }

            $filename = "payout-" . $history->id . "-" . time() . "-upload";

            $history->csv_file_upload = $filename;

            $file->storeAs(
                'csv/admin/pay_commission', "$filename.csv", "public"
            );

            $history->num = PaymentHistory::where("year", $history->year)->lockForUpdate()->max('num') + 1;
            $history->receipt_num = str_pad($history->num, 4, "0", STR_PAD_LEFT) . "/" . $history->year;
            $history->status = PaymentHistory::STATUS_COMPLETED;
            $history->save();

            return $history;
        });

        return $history;
    }

    public function cancelUpload(PaymentHistory $history)
    {
        $history = DB::transaction(function() use ($history) {
            if($history->status !== PaymentHistory::STATUS_PENDING_UPLOAD) {
                throw new AlertException("Payout file was already been processed. (Status: $history->status)");
            }

            DB::table("cm_commission_payouts AS p")
                ->join("cm_payment_details AS d", "d.payout_id", "=", "p.id")
                ->join("cm_payments AS py", "py.id", "=", "d.payment_id")
                ->where("py.history_id", $history->id)
                ->update(["p.is_paid" => 0]);

            $history->status = PaymentHistory::STATUS_CANCELLED_UPLOAD;
            $history->save();

        });

        return $history;
    }
}