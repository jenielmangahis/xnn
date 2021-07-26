<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Commissions\CommissionTypes\SparkleStartProgram;

class RunSparkleStartProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-sparkle-start-program';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Sparkle Start Program';

    protected $sparkleStartProgram;

    /**
     * Create a new command instance.
     *
     * @param SparkleStartProgram $sparkleStartProgram
     */
    public function __construct(SparkleStartProgram $sparkleStartProgram)
    {
        parent::__construct();
        $this->sparkleStartProgram = $sparkleStartProgram;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Time started - ' . Carbon::now());
        
        $this->sparkleStartProgram->processSparkelStartProgram();

        $this->info("Done running Sparkle Start Program");
        $this->info('Time ended - ' . Carbon::now());
    }
}
