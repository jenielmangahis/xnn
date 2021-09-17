<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class ArbitraryPoints extends Model
{
    protected $table = "cm_arbitrary_points";
    protected $fillable = [
        'bonus_points',
        'is_deleted',
    ];
}