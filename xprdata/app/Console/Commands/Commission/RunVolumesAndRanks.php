<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Commissions\VolumesAndRanks;
use Illuminate\Console\Command;

class RunVolumesAndRanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-volumes-and-ranks {date? : The date to run (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Volumes and Ranks';

    protected $volumesAndRanks;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(VolumesAndRanks $volumesAndRanks)
    {
        parent::__construct();
        $this->volumesAndRanks = $volumesAndRanks;
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
            $this->volumesAndRanks->run($this->argument('date'));
        }catch (\Exception $ex) {
            if(strpos($ex->getMessage(), 'Lock wait timeout exceeded') === false && strpos($ex->getMessage(), 'Deadlock found') === false) {
                throw $ex;
            }
        }

        $this->info("Done running daily ranks and volume for " . $this->volumesAndRanks->getEndDate());
        $this->info('Time ended - ' . Carbon::now());
    }
}
