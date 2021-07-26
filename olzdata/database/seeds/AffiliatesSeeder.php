<?php

use Illuminate\Database\Seeder;
use \Illuminate\Support\Facades\DB as DB;

class AffiliatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $users= DB::table('users as u')
            ->join('categorymap as cm', 'u.id', '=', 'cm.userid')
            ->whereRaw('find_in_set(catid, "'.config('commission.member-types.affiliates').'") 
            AND id not in (SELECT user_id FROM cm_affiliates)
            ')
            ->select('u.id', 'cm.catid', 'u.modified')
            ->get();


        foreach($users as $user){
            $affiliate = new App\Affiliate();
            $affiliate->cat_id = $affiliate->initial_cat_id = $user->catid;
            $affiliate->affiliated_at = $user->modified;
            $affiliate->user_id = $user->id;
            $affiliate->save();
        }
    }
}
