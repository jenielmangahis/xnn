<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class AchievedRank extends Model
{
    protected $table = "cm_achieved_ranks";

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfMember($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function scopeRank($query, $rank_id)
    {
        return $query->where('rank_id', $rank_id);
    }

    public function scopeHighest($query)
    {
        return $query->orderBy('rank_id', 'desc');
    }
}