<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMatrix extends Model
{
    protected $table = "cm_genealogy_matrix";
    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'parent_id', 'created_at'];

    public function sponsor()
    {
        return $this->belongsTo(UserMatrix::class, 'parent_id', 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
