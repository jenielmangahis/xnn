<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Commissions\Member\MatrixTree;
use Illuminate\Console\Command;

class ProcessMatrixTree extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:process-matrix-tree';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all unplaced users in the matrix tree';

    protected $matrix_tree;

    /**
     * Create a new command instance.
     *
     * @param MatrixTree $matrix_tree
     */
    public function __construct(MatrixTree $matrix_tree)
    {
        parent::__construct();
        $this->matrix_tree = $matrix_tree;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Time started - ' . Carbon::now());

        $this->matrix_tree->process();

        $this->info('Time ended - ' . Carbon::now());
    }
}
