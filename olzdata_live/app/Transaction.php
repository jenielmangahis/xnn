<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = "transactions";

    public $timestamps = false;


    public function scopeValidTransactions($query)
    {
        return $query->where('status', 'Approved')
            ->where(function($q) {
                $q->whereNull('authcode')
                    ->orWhere('authcode', '<>', 'No Charge');
            })
            ->where(function($q){
                $q->whereNull('credited')
                    ->orWhere('credited', '');
            });
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }


    public function products(){
        return $this->hasMany(\App\TransactionProduct::class, 'transaction_id');
    }
}
