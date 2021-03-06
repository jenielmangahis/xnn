<?php


namespace Commissions\Admin;

use App\Clawback;
use App\CommissionAdjustment;
use App\CommissionType;
use App\GiftCard;
use App\CommissionPeriod;
use App\BackgroundWorker;
use App\BackgroundWorkerProcess;
//use Commissions\Admin\Clawback;
use App\Ledger;
use App\LedgerPayout;
use App\OfficeGiftCard;
use Commissions\BackgroundWorkerLogger;
use Commissions\CommissionTypes\ImmediateEarnings;
use Commissions\CommissionTypes\MonthlyImmediateEarningsTrueUp;
use Commissions\CommissionTypes\UnilevelResidual;
use Commissions\CommissionTypes\GenerationResidual;
use Commissions\CsvReport;
use Commissions\CommissionTypes\SampleCommission;
use Commissions\CommissionTypes\PersonallyEnrolledAccountResidual;
use Commissions\Exceptions\AlertException;
use Commissions\Repositories\PayoutRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RunCommission
{
    const RUN_SCRIPT = 'artisan commission:run-commission';
    const LOG_PATH = "logs/run_commission";
    const CHUNK_COUNT = 2000;

    public function lock($commission_period_id)
    {
        $result = DB::transaction(function() use ($commission_period_id){

            $commission_period = CommissionPeriod::lockForUpdate()->findOrFail($commission_period_id);

            if ($commission_period == null) throw new AlertException("Commission period not found");

            if (+$commission_period->is_locked === 1) throw new AlertException("The commission period is already locked!");

            if(Carbon::createFromFormat('Y-m-d', $commission_period->end_date)->greaterThan(Carbon::now())) throw new AlertException("Unable to lock the period. The period has not yet ended.");

            Clawback::updateClawbackPayoutsByPeriod($commission_period_id);
            GiftCard::generateOfficeGiftCardsByPeriod($commission_period_id);
            // $this->processLedger($commission_period);

            $commission_period->is_locked = 1;
            $commission_period->save();

            return $commission_period;
        });

        return $result;
    }

    private function processLedger(CommissionPeriod $period)
    {
        if(config("commission.payout_to_ledger_enable", false) === false) return;

        $type = $period->type;

        if($type->payout_type !== CommissionType::PAYOUT_TYPE_CASH) return;

        $payouts = DB::table('cm_commission_payouts AS p')
            ->selectRaw("
                SUM(p.amount) amount, p.payee_id AS user_id, GROUP_CONCAT(p.id SEPARATOR ',') payout_ids
            ")
            ->where("p.commission_period_id", $period->id)
            ->groupBy('p.payee_id')
            ->get();

        foreach ($payouts as $payout) {
            $ledger = new Ledger();
            $ledger->user_id = $payout->user_id;
            $ledger->amount = $payout->amount;
            $ledger->notes = $type->name . " (" . $period->start_date . " - " . $period->end_date . ")";
            $ledger->type = Ledger::TYPE_COMMISSION;
            $ledger->reference_number = $period->id;
            $ledger->save();

            foreach(explode(",", $payout->payout_ids) as $payout_id) {
                $ledger_payout = new LedgerPayout();
                $ledger_payout->ledger_id = $ledger->id;
                $ledger_payout->payout_id = $payout_id;
                $ledger_payout->save();
            }
        }
    }

    public function complete($id)
    {
        DB::transaction(function () use ($id) {

            $background_worker = BackgroundWorker::lockForUpdate()->findOrFail($id);

            if ($background_worker->is_running !== 'YES') {
                throw new AlertException("Commission period is already {$background_worker->is_running}");
            }

            $background_worker->is_running = 'COMPLETED';

            $background_worker->save();
        });

        return $this->details($id);
    }

    public function run($commission_period_id)
    {

        $commission_period = CommissionPeriod::find($commission_period_id);

        if ($commission_period == null) throw new AlertException("Commission period not found");

        if (+$commission_period->is_locked === 1) throw new AlertException("The commission period is already locked");

        $background_worker = BackgroundWorker::where([
            'commission_period_id' => $commission_period_id,
            'is_running' => 'YES',
            'type' => BackgroundWorker::TYPE_RUN_COMMISSION
        ])->orderBy('created_at', 'desc')->first();

        if ($background_worker != null) {
            throw new AlertException("The commission period is already running", "warning", $this->details($background_worker->id));
        }

        $commission_type_id = +$commission_period->commission_type_id;

        try {

            $background_worker = DB::transaction(function () use ($commission_period_id, $commission_type_id) {

                $commission = static::getCommissionTypeClass($commission_type_id, $commission_period_id);
                $is_single_process = $commission->isSingleProcess();
                $chunk = static::CHUNK_COUNT;
                $count = $commission->count();

                $commission->beforeCommissionRun();

                Clawback::deleteClawbackPendingPayoutsByPeriod($commission_period_id);
                GiftCard::deleteByPeriod($commission_period_id);

                DB::table("cm_commission_payouts")
                    ->join('cm_commission_periods AS pr', 'pr.id', '=', 'cm_commission_payouts.commission_period_id')
                    ->where('pr.is_locked', 0)
                    ->where('pr.id', $commission_period_id)
                    ->delete();

                $chunk_count = 1;

                if(!$is_single_process) {
                    $chunk_count = ceil($count / $chunk);
                }

                $background_worker = new BackgroundWorker;
                $background_worker->value = "{period_id:" . $commission_period_id . "}";
                $background_worker->total_task = $chunk_count + 3; // +3 for adjustment, claw back, and csv;
                $background_worker->total_task_done = 0;
                $background_worker->commission_period_id = $commission_period_id;
                $background_worker->is_running = 'YES';
                $background_worker->loop = $count + 6; // +6 for adjustment, claw back, and csv;
                $background_worker->type = BackgroundWorker::TYPE_RUN_COMMISSION;
                $background_worker->save();

                $logger = new BackgroundWorkerLogger(storage_path(static::LOG_PATH), $background_worker->id);

                $pids = [];

                try {
                    for ($i = 0; $i < $chunk_count; $i++) {
                        $offset = $i * $chunk;

                        if($is_single_process) {
                            $limit = $count;
                        } else {
                            $limit = $chunk;
                        }

                        $process = $background_worker->processes()->save(new BackgroundWorkerProcess([
                            'status' => BackgroundWorkerProcess::STATUS_RUNNING,
                            'order' => 0,
                            'offset' => $offset,
                            'count' => $limit,
                            'type' => BackgroundWorkerProcess::TYPE_PAYOUT,
                        ]));

                        // $command =  'php --version > /dev/null 2>&1 & echo $!; ';
                        $command = "php -f " . base_path(static::RUN_SCRIPT) . " {$commission_period_id} {$offset} {$limit} {$background_worker->id} {$process->id} {$commission_type_id} payout &>> {$logger->getFilePath()} & echo $!; ";

                        $pid = exec($command, $output);
                        $pids[] = $pid;

                        $process->pid = $pid;
                        $process->save();
                    }

                    // ADJUSTMENT
                    $adjustment_process = $background_worker->processes()->save(new BackgroundWorkerProcess([
                        'pid' => 0,
                        'status' => BackgroundWorkerProcess::STATUS_PENDING,
                        'order' => 1,
                        'offset' => 0,
                        'count' => 0,
                        'type' => BackgroundWorkerProcess::TYPE_ADJUSTMENT,
                    ]));

                    // CLAWBACK
                    $background_worker->processes()->save(new BackgroundWorkerProcess([
                        'pid' => 0,
                        'status' => BackgroundWorkerProcess::STATUS_PENDING,
                        'order' => 2,
                        'offset' => 0,
                        'count' => 0,
                        'type' => BackgroundWorkerProcess::TYPE_CLAWBACK,
                    ]));
                    // CSV - REPORT
                    $background_worker->processes()->save(new BackgroundWorkerProcess([
                        'pid' => 0,
                        'status' => BackgroundWorkerProcess::STATUS_PENDING,
                        'order' => 3,
                        'offset' => 0,
                        'count' => 0,
                        'type' => BackgroundWorkerProcess::TYPE_CSV,
                    ]));

                    if(count($pids) === 0) {
                        $command = "php -f " . base_path(static::RUN_SCRIPT) . " {$commission_period_id} 0 0 {$background_worker->id} {$adjustment_process->id} {$commission_type_id} adjustment &>> {$logger->getFilePath()} & echo $!; ";
                        $pid = exec($command, $output);
                        $pids[] = $pid;
                    }

                } catch (\Exception $ex) {

                    foreach ($pids as $pid) {

                        $load = exec("ps -o pid,command -p " . $pid, $output);

                        if (strpos($load, $pid) !== false && strpos($load, base_path(static::RUN_SCRIPT)) !== false) {
                            exec("kill -9 {$pid}", $output);
                        }

                    }

                    throw $ex;
                }

                return $background_worker;
            });

        }catch (\Exception $ex) {
            throw new AlertException($ex->getMessage());
        }

        return $this->details($background_worker->id);
    }

    public function getPreviousRun($id)
    {
        $commission_period = CommissionPeriod::find($id);

        if ($commission_period == null) throw new AlertException("Commission period not found");

        $background_worker = BackgroundWorker::where([
            'commission_period_id' => $id,
            'type' => BackgroundWorker::TYPE_RUN_COMMISSION
        ])->orderBy('created_at', 'desc')->first();

        if ($background_worker == null) {

            throw new AlertException("No previous run found for this commission period", "warning");
        }

        return $this->details($background_worker->id);

    }

    public function cancel($id)
    {
        DB::transaction(function () use ($id) {

            $background_worker = BackgroundWorker::lockForUpdate()->findOrFail($id);

            if ($background_worker->is_running !== 'YES') {
                throw new AlertException("Commission period is already {$background_worker->is_running}");
            }

            $background_worker->is_running = 'CANCELLED';

            $background_worker->save();

            $running_processes = $background_worker->processes()->running()->get();

            foreach ($running_processes as $p) {
                $load = trim(exec("ps -o pid,command -p " . $p->pid, $output));

                if (strpos($load, $p->pid) !== false && strpos($load, base_path(static::RUN_SCRIPT)) !== false) {
                    exec("kill -9 {$p->pid}", $output);
                }
            }

            $background_worker->processes()->pendingOrRunning()->update(['status' => BackgroundWorkerProcess::STATUS_CANCELLED]);

        });

        return $this->details($id);
    }

    public function log($id, $seek)
    {
        $logger = new BackgroundWorkerLogger(storage_path(static::LOG_PATH), +$id);
        return $logger->getContent($seek);
    }

    public function details($id)
    {
        $background_worker = BackgroundWorker::findOrFail($id);

        $processes = $background_worker->processes;

        $this->getServerLoad($processes);

        return [
            'background' => $background_worker,
            'processes' => $processes,
        ];
    }

    private function getServerLoad(&$processes)
    {
        foreach ($processes as &$process) {
            if ($process->status == BackgroundWorkerProcess::STATUS_RUNNING) {

                $load = trim(exec("ps -o pid,%cpu,%mem,user,command -p " . $process->pid, $output));

                if (strpos($load, $process->pid) !== false && strpos($load, base_path(static::RUN_SCRIPT)) !== false) {
                    $l = explode(" ", $load);
                    $process->cpu = is_numeric($l[2]) ? $l[2] : "Fetching";
                    $process->mem = is_numeric($l[4]) ? $l[4] : "Fetching";
                } else {
                    $process->cpu = 'N/A';
                    $process->mem = 'N/A';
                }

            } else {
                $process->cpu = 'N/A';
                $process->mem = 'N/A';
            }
        }
    }

    public static function execute($period_id, $start, $limit, $background_worker_id , $background_worker_process_id, $commission_type_id , $process_type)
    {
        try {

            $commission = static::getCommissionTypeClass($commission_type_id, $period_id, $background_worker_id, $background_worker_process_id);
            $logger = new BackgroundWorkerLogger(storage_path(static::LOG_PATH), $background_worker_id, $background_worker_process_id);

            if ($process_type === BackgroundWorkerProcess::TYPE_PAYOUT) {

                $commission->generateCommission($start, $limit);

            } else if ($process_type === BackgroundWorkerProcess::TYPE_ADJUSTMENT) {

                $logger->log("          ");

                CommissionAdjustment::generateAdjustmentByPeriod($period_id);

                $logger->log("          ");

            } else if ($process_type === BackgroundWorkerProcess::TYPE_CLAWBACK) {

                $logger->log("          ");

                // $logger->log("############################## THE CLAWBACK IS NOT IMPLEMENTED ##############################");
                $clawback = new \Commissions\Admin\Clawback();
                $clawback->processClawbacks($period_id);

                $logger->log("          ");

            } else if ($process_type === BackgroundWorkerProcess::TYPE_CSV) {

                $logger->log("Generating CSV reports");
                $commission->afterCommissionRun();

                $b = BackgroundWorkerProcess::find($background_worker_process_id)->worker;

                $logger->log("          ");
                // $links = $commission->generateReportLinks($b->id . "_");

                $csv_report = new CsvReport("csv/admin/run_commission");

                $file_name = $b->id . "_" . $commission->getCommissionType() . "_" . $commission->getPeriodStartDate() . "_" . $commission->getPeriodEndDate();
                $download_link = $csv_report->generateLink(
                    "{$file_name}_payout_summary",
                    $commission->getSummary()
                );

                $download_details_link = $csv_report->generateLink(
                    "{$file_name}_payout_details",
                    $commission->getDetails()
                );

                $links = [
                    'download_link' => $download_link,
                    'download_details_link' => $download_details_link
                ];

                DB::transaction(function () use ($background_worker_process_id, $links) {
                    $process = BackgroundWorkerProcess::with(['worker' => function ($query) {
                        $query->lockForUpdate();
                    }])->lockForUpdate()->find($background_worker_process_id);

                    $background_worker = $process->worker;

                    $background_worker->download_link = $links['download_link'];
                    $background_worker->download_details_link = $links['download_details_link'];
                    $background_worker->is_report_generated = 1;
                    $background_worker->save();
                });

                $logger->log("          ");

                if($links['download_link'] !== null && $links['download_link'] !== "") {
                    $commission->log("Payout Summary: " . $links['download_link']);
                }

                if($links['download_details_link'] !== null && $links['download_details_link'] !== "") {
                    $commission->log("Payout Details: " . $links['download_details_link']);
                }

                if($links['download_link'] == null && $links['download_details_link'] == null) {
                    $commission->log("NO COMMISSION REPORT");
                } else {
                    $commission->log("Done generating CSV reports");
                }
            }

            DB::transaction(function () use ($background_worker_process_id, $period_id, $commission_type_id ,$commission, $logger) {

                $process = BackgroundWorkerProcess::with(['worker' => function ($query) {
                    $query->lockForUpdate();
                }])->lockForUpdate()->find($background_worker_process_id);

                if ($process != null) {
                    $process->status = BackgroundWorkerProcess::STATUS_DONE;
                    $process->save();

                    $b = $process->worker;
                    $b->total_task_done++;

                    $b->save();

                    $remaining = $b->total_task - $b->total_task_done;

                    if ($remaining == 3) { // adjustments
                        $process = BackgroundWorkerProcess::where(['worker_id' => $b->id, 'type' => BackgroundWorkerProcess::TYPE_ADJUSTMENT])->first();

                        $command = "php -f " . base_path(static::RUN_SCRIPT) . " {$b->commission_period_id} 0 0 {$b->id} {$process->id} {$commission_type_id} adjustment &>> {$logger->getFilePath()} & echo $!; ";

                        $pid = exec($command, $output);

                        $process->pid = $pid;
                        $process->status = BackgroundWorkerProcess::STATUS_RUNNING;

                        $process->save();

                    } else if ($remaining == 2) { // clawbacks
                        $process = BackgroundWorkerProcess::where(['worker_id' => $b->id, 'type' => BackgroundWorkerProcess::TYPE_CLAWBACK])->first();

                        $command = "php -f " . base_path(static::RUN_SCRIPT) . " {$b->commission_period_id} 0 0 {$b->id} {$process->id} {$commission_type_id} clawback &>> {$logger->getFilePath()} & echo $!; ";

                        $pid = exec($command, $output);

                        $process->pid = $pid;
                        $process->status = BackgroundWorkerProcess::STATUS_RUNNING;

                        $process->save();

                    } else if ($remaining == 1) { // csv
                        $process = BackgroundWorkerProcess::where(['worker_id' => $b->id, 'type' => BackgroundWorkerProcess::TYPE_CSV])->first();

                        $command = "php -f " . base_path(static::RUN_SCRIPT) . " {$b->commission_period_id} 0 0 {$b->id} {$process->id} {$commission_type_id} csv &>> {$logger->getFilePath()} & echo $!; ";

                        $pid = exec($command, $output);

                        $process->pid = $pid;
                        $process->status = BackgroundWorkerProcess::STATUS_RUNNING;

                        $process->save();
                    }
                }

            });

        } catch (\Exception $ex) {

            DB::transaction(function () use ($background_worker_process_id) {

                $process = BackgroundWorkerProcess::with(['worker' => function ($query) {
                    $query->lockForUpdate();
                }])->lockForUpdate()->find($background_worker_process_id);

                if ($process != null) {
                    $process->status = BackgroundWorkerProcess::STATUS_FAILED;
                    $process->save();

                    $b = $process->worker;
                    // $b->total_task_done = $b->total_task;
                    $b->is_running = 'FAILED';
                    $b->save();
                }

                $runningProcesses = $b->processes()->running()->get();

                foreach ($runningProcesses as $p) {
                    $load = trim(exec("ps -o pid,command -p " . $p->pid, $output));

                    if (strpos($load, $p->pid) !== false && strpos($load, base_path(static::RUN_SCRIPT)) !== false) {
                        exec("kill -9 {$p->pid}", $output);
                    }
                }

                $b->processes()->pendingOrRunning()->update(['status' => BackgroundWorkerProcess::STATUS_CANCELLED]);

            });

            throw $ex;
        }
    }

    public static function getCommissionTypeClass($commission_type_id, $commission_period_id, $background_worker_id = null, $background_worker_process_id = null)
    {
        $period = CommissionPeriod::find($commission_period_id);
        $background_worker_logger = new BackgroundWorkerLogger(storage_path(static::LOG_PATH), $background_worker_id, $background_worker_process_id);
        $payout_repository = new PayoutRepository();

        switch (+$commission_type_id) {
            case config('commission.commission-types.personally-enrolled-account-residual'):
                return new PersonallyEnrolledAccountResidual($period, $background_worker_logger, $payout_repository);
            case config('commission.commission-types.unilevel-residual'):
                return new UnilevelResidual($period, $background_worker_logger, $payout_repository);
            case config('commission.commission-types.monthly-immediate-earnings-true-up'):
                return new MonthlyImmediateEarningsTrueUp($period, $background_worker_logger, $payout_repository);
            case config('commission.commission-types.weekly-immediate-earnings'):
                return new ImmediateEarnings($period, $background_worker_logger, $payout_repository);
            case config('commission.commission-types.generation-residuals'):
                return new GenerationResidual($period, $background_worker_logger, $payout_repository);
            default:
                return new SampleCommission($period, $background_worker_logger, $payout_repository);
        }
    }
}