<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Commissions\ProFreePlan;
use Illuminate\Console\Command;

class RunProFreePlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-pro-free-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Pro (Free) Plan';

    protected $proFreePlan;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ProFreePlan $proFreePlan)
    {
        parent::__construct();
        $this->proFreePlan = $proFreePlan;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Time started - ' . Carbon::now());

        $this->proFreePlan->process();

        $this->info("Done running Pro (Free) Plan");
        $this->info('Time ended - ' . Carbon::now());
    }
}
