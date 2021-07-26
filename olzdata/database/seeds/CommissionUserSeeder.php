<?php

use Illuminate\Database\Seeder;
use \Illuminate\Support\Facades\Artisan as Artisan;

class CommissionUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class, 5)->create()->each(function($user){
        });
        $this->call(AffiliatesSeeder::class);
        $this->call(CategoryMapSeeder::class);
        $this->call(TransactionSeeder::class);


    }
}
