<?php


namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MinimumRank extends Model
{
    use SoftDeletes;

    protected $table = "cm_minimum_ranks";
}