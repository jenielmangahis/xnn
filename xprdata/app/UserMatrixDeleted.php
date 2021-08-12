<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMatrixDeleted extends Model
{
    protected $table = "cm_genealogy_matrix_deleted";
    
    protected $fillable = ['user_id', 'parent_id', 'placed_at', 'upline_id'];
}
