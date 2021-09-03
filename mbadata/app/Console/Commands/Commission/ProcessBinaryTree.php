<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Commissions\Member\BinaryTree;
use Illuminate\Console\Command;

class ProcessBinaryTree extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:process-binary-tree';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all unplaced users in the binary tree';

    protected $binaryTree;

    /**
     * Create a new command instance.
     *
     * @param BinaryTree $binaryTree
     */
    public function __construct(BinaryTree $binaryTree)
    {
        parent::__construct();
        $this->binaryTree = $binaryTree;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Time started - ' . Carbon::now());

        $this->binaryTree->process();

        $this->info('Time ended - ' . Carbon::now());
    }
}
