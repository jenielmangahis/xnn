<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Commissions\CommissionTypes\BuilderBonus;
use Illuminate\Console\Command;

class RunBuilderBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-builder-bonus {date? : The date to run (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Builder Bonus';

    protected $builderBonus;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BuilderBonus $builderBonus)
    {
        parent::__construct();
        $this->builderBonus = $builderBonus;
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
            $this->builderBonus->run($this->argument('date'));
        }catch (\Exception $ex) {
            if(strpos($ex->getMessage(), 'Lock wait timeout exceeded') === false && strpos($ex->getMessage(), 'Deadlock found') === false) {
                throw $ex;
            }
        }

        $this->info("Done running builder bonus for " . $this->builderBonus->getEndDate());
        $this->info('Time ended - ' . Carbon::now());
    }
}
