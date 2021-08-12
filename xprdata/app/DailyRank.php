<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DailyRank extends Model
{
    protected $table = 'cm_daily_ranks';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function volume()
    {
        return $this->belongsTo(DailyVolume::class, "volume_id", 'id');
    }

    public function paidAsRank()
    {
        return $this->belongsTo(Rank::class, "paid_as_rank", "id");
    }

    public function minimumRank()
    {
        return $this->belongsTo(Rank::class, "minimum_rank_id", "id");
    }

    public function scopeDate($query, $rank_date)
    {
        return $query->where('rank_date', $rank_date);
    }

    public function scopeOfMember($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function scopeToday($query)
    {
        return $query->where("rank_date", DB::raw("CURRENT_DATE()"));
    }

}
