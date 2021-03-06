<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    const HOSTESS_PLAN  = 80362;
    const CUSTOMER_PLAN = 15;
    const PRO_PLAN      = 13;


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public $timestamps = false;

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsorid');
    }

    public function ranks()
    {
        return $this->hasMany(DailyRank::class, 'user_id');
    }

    public function isSelfOrExistsOnEnrollerDownline($user_id)
    {
        if (+$this->id === +$user_id) return true;

        return static::whereRaw("EXISTS(
                WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`
                    FROM users
                    WHERE sponsorid = ? AND levelid = 3
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id
                    WHERE p.levelid = 3
                )
                SELECT 1 FROM downline d WHERE d.user_id = users.id
            )", [$this->id])->where("id", $user_id)->count(DB::raw("1")) > 0;
    }

    public function isSelfOrExistsOnPlacementDownline($user_id)
    {
        if (+$this->id === +$user_id) return true;

        $affiliates = config('commission.member-types.affiliates');

        return static::whereRaw("EXISTS(
                WITH RECURSIVE downline (user_id, sponsor_id, `level`) AS (
                    SELECT 
                        p.user_id,
                        p.sponsor_id,
                        1 AS `level`
                    FROM cm_genealogy_placement p
                    JOIN users u ON u.id = p.user_id
                    WHERE u.levelid = 3
                        AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                        AND p.sponsor_id = ?
                    
                    UNION ALL
                    
                    SELECT
                        p.user_id,
                        p.sponsor_id,
                        downline.`level` + 1 `level`
                    FROM cm_genealogy_placement p
                    JOIN users u ON u.id = p.user_id
                    INNER JOIN downline ON p.sponsor_id = downline.user_id
                    WHERE u.levelid = 3
                        AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                )
                SELECT 1 FROM downline d WHERE d.user_id = users.id
            )", [$this->id])->where("id", $user_id)->count(DB::raw("1")) > 0;
    }

    public function isSelfOrExistsOnMatrixDownline($user_id)
    {
        if (+$this->id === +$user_id) return true;

        $affiliates = config('commission.member-types.affiliates');

        return static::whereRaw("EXISTS(
                WITH RECURSIVE downline (user_id, parent_id, `level`, created_at, path) AS (
                    SELECT 
                        gm.user_id,
                        gm.parent_id,
                        0 AS `level`,
                        gm.created_at,
                        CONCAT(gm.user_id) path
                    FROM cm_genealogy_matrix AS gm
                    WHERE gm.parent_id = ?
                    
                    UNION ALL
                    
                    SELECT
                        gm.user_id,
                        gm.parent_id,
                        d.`level` + 1 `level`,
                        gm.created_at,
                        CONCAT(d.path, ',', gm.user_id) path
                    FROM cm_genealogy_matrix AS gm
                    JOIN downline AS d ON d.user_id = gm.parent_id
                )
                SELECT 1 FROM downline d WHERE d.user_id = users.id
            )", [$this->id])->where("id", $user_id)->count(DB::raw("1")) > 0;
    }



}
