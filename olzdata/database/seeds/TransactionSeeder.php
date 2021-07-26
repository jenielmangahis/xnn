<?php

use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Transaction::class, 10)->create()->each(function ($transaction){
            $max_tp = rand(1, 5);
            for($i=1; $i<=$max_tp; $i++)
            {
                $transaction->products()->save(factory(App\TransactionProduct::class)->create());
                $transaction->sub_total = $transaction->products->sum('total');
                $transaction->amount = $transaction->products->sum('total');
                $transaction->save();
            }
        });
    }
}