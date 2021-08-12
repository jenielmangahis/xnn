<?php

namespace App\Console;

use App\Console\Commands\Commission\GenerateCommissionPeriods;
use App\Console\Commands\Commission\ProcessMatrixTree;
use App\Console\Commands\Commission\RunCommission;
use App\Console\Commands\Commission\RunLedgerNotification;
use App\Console\Commands\Commission\RunPlacement;
use App\Console\Commands\Commission\RunVolumesAndRanks;
use App\Console\Commands\Commission\SetPurchaserSponsorCategoryID;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GenerateCommissionPeriods::class,
        RunVolumesAndRanks::class,
        SetPurchaserSponsorCategoryID::class,
        RunCommission::class,
        RunLedgerNotification::class,
        RunPlacement::class,
        ProcessMatrixTree::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
