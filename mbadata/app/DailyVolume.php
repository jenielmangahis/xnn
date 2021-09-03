<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DailyVolume extends Model
{
    protected $table = "cm_daily_volumes";
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rank()
    {
        return $this->hasOne(DailyRank::class, "volume_id", "id");
    }

    public function scopeDate($query, $volume_date)
    {
        return $query->where('volume_date', $volume_date);
    }

    public function scopeOfMember($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function scopeToday($query)
    {
        return $query->where("volume_date", DB::raw("CURRENT_DATE()"));
    }

}
