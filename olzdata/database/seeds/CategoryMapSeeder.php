<?php

use Illuminate\Database\Seeder;

class CategoryMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $users = App\User::whereRaw('id not in (select userid from categorymap)')
            ->get();

        $affiliates = explode(',', config('commission.member-types.affiliates'));
        $customers = explode(',', config('commission.member-types.customers'));
        $catids = array_merge($affiliates, $customers);

        foreach($users as $user){
            $cat = new App\CategoryMap;
            $cat->userid = $user->id;
            $cat->catid = $catids[rand(0, count($catids)-1)];
            $cat->save();
        }
    }
}
