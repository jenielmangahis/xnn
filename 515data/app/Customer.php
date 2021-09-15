<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = "customers";

    protected $hidden = [
        'password',
    ];

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsorid');
    }

    public $timestamps = false;
}