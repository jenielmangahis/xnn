<?php

namespace App\Console\Commands\Commission;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InsertMissingUserPlacement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:insert-missing-user-placement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert missing user in placement tree.';

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
                
                $affiliate_category = config('commission.member-types.affiliates');
    
                DB::statement("
                    INSERT INTO cm_genealogy_history (user_id, old_parent_id, tree_id)
                    SELECT u.id, u.sponsorid, 2
                    FROM users u 
                    WHERE u.levelid = 3 AND u.active = 'Yes' AND NOT EXISTS (SELECT 1 FROM cm_genealogy_placement WHERE u.id = user_id) AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id);
                ");
    
                DB::statement("
                    INSERT INTO cm_genealogy_placement (user_id, sponsor_id, expired_at, is_placed, placed_at)
                    SELECT
                        u.id,
                        u.sponsorid,
                        DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY),
                        IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliate_category')), 0, 1),
                        IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliate_category')), NULL, CURRENT_DATE())
                    FROM users u 
                    WHERE u.levelid = 3 AND u.active = 'Yes' AND NOT EXISTS (SELECT 1 FROM cm_genealogy_placement WHERE u.id = user_id) AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id);
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
