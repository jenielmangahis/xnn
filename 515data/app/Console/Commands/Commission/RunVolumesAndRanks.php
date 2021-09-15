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
    	/*
		if ($this->argument('date') === 'tioat') {
			$this->info('Time started to run long migration - ' . Carbon::now());
			
			$dates = [
				'2021-01-03',
				'2021-01-10',
				'2021-01-17',
				'2021-01-24',
				'2021-01-31',
				'2021-02-07',
				'2021-02-14',
				'2021-02-21',];

			foreach ($dates as $date) {
				$this->info('Time started - ' . Carbon::now());

				try {
					$this->volumesAndRanks->run($date);
				}catch (\Exception $ex) {
					if(strpos($ex->getMessage(), 'Lock wait timeout exceeded') === false && strpos($ex->getMessage(), 'Deadlock found') === false) {
						throw $ex;
					}
				}
	
				$this->info("Done running daily ranks and volume for " . $this->volumesAndRanks->getEndDate());
				$this->info('Time ended - ' . Carbon::now());
			}
			

		} else {
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
		*/

       // if($this->argument('date') === 'now' && date('Y-m-d') === '2021-06-16') return;

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
