<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPlacement extends Model
{
    protected $table = 'cm_genealogy_placement';
    protected $primaryKey = 'user_id';

    public function sponsor()
    {
        return $this->belongsTo(UserPlacement::class, 'sponsor_id', 'user_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
