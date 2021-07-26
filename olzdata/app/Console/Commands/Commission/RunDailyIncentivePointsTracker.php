<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Commissions\IncentivePointsTracker;
use Illuminate\Console\Command;

class RunDailyIncentivePointsTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-daily-incentive-points-tracker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run daily incentive points tracker';

    protected $incentivePointsTracker;

    /**
     * Create a new command instance.
     *
     * @param IncentivePointsTracker $incentivePointsTracker
     */
    public function __construct(IncentivePointsTracker $incentivePointsTracker)
    {
        parent::__construct();
        $this->incentivePointsTracker = $incentivePointsTracker;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Time started - ' . Carbon::now());
        try {
            $this->incentivePointsTracker->run();
        }catch (\Exception $ex) {
            if(strpos($ex->getMessage(), 'Lock wait timeout exceeded') === false && strpos($ex->getMessage(), 'Deadlock found') === false) {
                throw $ex;
            }
        }
        

        $this->info("Done running Sparkle Start Program");
        $this->info('Time ended - ' . Carbon::now());
    }
}
