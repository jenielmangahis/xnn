<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RankConsistency extends Model
{
    protected $table = 'cm_rank_consistency';

    public $timestamps = false;

    public function scopeOfPeriod($query, $period_id)
    {
        return $query->where('commission_period_id', $period_id);
    }

    public static function deleteByPeriod($period_id)
    {
        return static::ofPeriod($period_id)->delete();
    }

    public static function userIsQualified($user_id)
    {

        $sql = "
            SELECT 
                COUNT(rank_id) AS maintainedMonths
            FROM (
                SELECT 
                *
                FROM cm_daily_ranks cdr
                WHERE cdr.`rank_id` >= 4 -- minimum rank
                AND cdr.`rank_date` = LAST_DAY(cdr.`rank_date`)
                AND cdr.`rank_date` BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY) AND CURRENT_DATE()
                AND cdr.is_active = 1 AND cdr.is_system_active = 1
                AND cdr.user_id = $user_id
                GROUP BY LAST_DAY(cdr.`rank_date`)
                ORDER BY rank_id ASC
            ) AS ranks
            JOIN users u ON u.id = ranks.user_id
            JOIN cm_ranks r ON r.id = ranks.rank_id
            -- GROUP BY ranks.rank_id
            HAVING maintainedMonths >= 3
                ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();

        return +$stmt->fetchColumn() > 0;
    }
}
