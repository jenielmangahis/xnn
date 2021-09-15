<?php

namespace App\Console\Commands\Commission;


use Carbon\Carbon;
use Commissions\Admin\TechFeeEmails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:run-email-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notifications';

    protected $tech_fee_emails;

    /**
     * Create a new command instance.
     *
     * @param TechFeeEmails $techFeeEmails
     */
    public function __construct(TechFeeEmails $techFeeEmails)
    {
        parent::__construct();
        $this->tech_fee_emails = $techFeeEmails;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Time started - ' . Carbon::now());

        // $this->tech_fee_emails->first_email();
        // $this->tech_fee_emails->second_email();
        // $this->tech_fee_emails->third_email();
        // $this->tech_fee_emails->fourth_email();
        // $this->tech_fee_emails->fifth_email();

        $this->info('Time ended - ' . Carbon::now());
    }
}
