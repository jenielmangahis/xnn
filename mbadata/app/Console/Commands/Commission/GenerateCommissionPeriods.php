<?php

namespace App\Console\Commands\Commission;

use App\CommissionType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateCommissionPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:generate-commission-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate commission periods';

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
        DB::transaction(function(){
            CommissionType::generateCommissionPeriods();
        }, 10);
    }
}
