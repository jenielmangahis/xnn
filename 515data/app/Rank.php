<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Rank extends Model
{
    protected $table = 'cm_ranks';

    public function scopeOfDefault($query)
    {
        return $query->where("id", 1);
    }

    public static function countLegRequirement($rank_id,$user_id)
    {
        $sql = "
            SELECT
                COUNT(rank_id) AS `count`
            FROM (
                WITH RECURSIVE downline (user_id, parent_id, root_id, `level`) AS (
                    SELECT 
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        u.id AS root_id,
                        1 AS `level`
                    FROM users u
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = CURRENT_DATE() AND u.sponsorid = $user_id
                    
                    UNION ALL
                    
                    SELECT
                        u.id AS user_id,
                        u.sponsorid AS parent_id,
                        downline.root_id,
                        downline.`level` + 1 `level`
                    FROM users u
                    JOIN downline ON u.sponsorid = downline.user_id
                    JOIN cm_daily_volumes dv ON dv.user_id = u.id
                    WHERE dv.volume_date = CURRENT_DATE()
                )
                SELECT
                    MAX(dr.rank_id) rank_id,
                    d.root_id 
                FROM downline AS d 
                JOIN cm_daily_ranks dr ON dr.user_id = d.user_id
                WHERE dr.rank_date = CURRENT_DATE() AND dr.rank_id >= $rank_id
            ) max_rank_per_leg;
        ";


        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }
}
