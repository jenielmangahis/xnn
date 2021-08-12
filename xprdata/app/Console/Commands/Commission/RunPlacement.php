<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Commissions\Member\PlacementTree;
use Illuminate\Console\Command;

class RunPlacement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-placement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Placement';

    protected $placement;

    /**
     * Create a new command instance.
     *
     * @param PlacementTree $placement
     */
    public function __construct(PlacementTree $placement)
    {
        parent::__construct();
        $this->placement = $placement;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // check for expired placement and send email notifications (3 days before end of 60 days)

        $this->info('Time started - ' . Carbon::now());

        // sends notification 3 days before 60-day holding tank
         $this->placement->sendNotification();

        // permanently place a member after 30-day holding tank period
        $this->placement->placeExpiredMember();

        $this->info("Done running Run Placement Checking");
        $this->info('Time ended - ' . Carbon::now());
    }
}
