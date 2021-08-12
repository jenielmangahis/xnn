<?php

namespace App\Console\Commands\Commission;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetPurchaserSponsorCategoryID extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:set-purchaser-sponsor-category-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set purchaser and sponsor category id in the transactions table.';

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
        try {
            DB::transaction(function(){
                $category_ids =  config('commission.member-types.customers');
                $category_ids .= ',';
                $category_ids .= config('commission.member-types.affiliates');

                DB::statement("
                    UPDATE transactions t
                    SET t.purchaser_catid = (
                        SELECT 
                            cm.catid 
                        FROM categorymap cm 
                        WHERE cm.userid = t.userid
                        ORDER BY FIND_IN_SET(cm.catid, '$category_ids') DESC
                        LIMIT 1
                    )
                    WHERE t.purchaser_catid IS NULL AND t.userid <> 0;
                ");

                DB::statement("
                    UPDATE transactions t
                    SET t.sponsor_catid = (
                        SELECT 
                            cm.catid 
                        FROM categorymap cm 
                        WHERE cm.userid = t.sponsorid
                        ORDER BY FIND_IN_SET(cm.catid, '$category_ids') DESC
                        LIMIT 1
                    )
                    WHERE t.sponsor_catid IS NULL AND t.sponsorid <> 0;
                ");
            }, 2);
        }
        catch (\Illuminate\Database\QueryException $ex) {
            if(strpos($ex->getMessage(), 'Lock wait timeout exceeded') === false && strpos($ex->getMessage(), 'Deadlock found') === false) {
                throw $ex;
            }
        }
    }
}
