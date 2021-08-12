<?php

namespace App\Console\Commands\Commission;

use Illuminate\Console\Command;

class RunCommission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-commission {period_id} {start} {limit} {background_worker_id} {process_id} {commission_type_id} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Commission';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $period_id = +$this->argument('period_id');
        $start = +$this->argument('start');
        $limit = +$this->argument('limit');
        $background_worker_id = +$this->argument('background_worker_id');
        $process_id = +$this->argument('process_id');
        $commission_type_id = +$this->argument('commission_type_id');
        $type = $this->argument('type');

        \Commissions\Admin\RunCommission::execute( $period_id, $start, $limit, $background_worker_id, $process_id, $commission_type_id, $type);
    }
}
