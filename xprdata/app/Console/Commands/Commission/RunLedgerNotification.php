<?php

namespace App\Console\Commands\Commission;

use Commissions\Member\Ledger;
use Illuminate\Console\Command;

class RunLedgerNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-ledger-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run ledger notification';

    protected $ledger;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Ledger $ledger)
    {
        parent::__construct();
        $this->ledger = $ledger;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->ledger->sendNotification();
    }
}
