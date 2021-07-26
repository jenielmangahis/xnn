<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Commissions\CommissionTypes\SilverStartUp;

class RunSilverStartUpProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-silver-start-up-program';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Silver Start Up Program';

    protected $silverStartUp;

    /**
     * Create a new command instance.
     *
     * @param SparkleStartProgram $sparkleStartProgram
     */
    public function __construct(SilverStartUp $silverStartUp)
    {
        parent::__construct();
        $this->silverStartUp = $silverStartUp;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Time started - ' . Carbon::now());
        
        $this->silverStartUp->run();

        $this->info("Done running Silver Start Up Program");
        $this->info('Time ended - ' . Carbon::now());
    }
}
