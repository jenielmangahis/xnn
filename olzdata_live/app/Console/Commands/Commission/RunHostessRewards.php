<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Commissions\Member\HostessRewards;

class RunHostessRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-hostess-rewards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Hostess Rewards.';

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
        $this->info('Time started - ' . Carbon::now());

        $hostess_rewards = new HostessRewards();
        $hostess_rewards->runRewards();

        $this->info('Done running Hostess Rewards.');
        $this->info('Time ended - ' . Carbon::now());
    }
    
}
