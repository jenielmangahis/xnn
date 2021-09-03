<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

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

    public function isSelfOrExistsOnBinaryDownline($user_id)
    {
        if (+$this->id === +$user_id) return true;

        $affiliates = config('commission.member-types.affiliates');

        return static::whereRaw("EXISTS(
                WITH RECURSIVE downline (user_id, parent_id, `level`, created_at, path) AS (
                    SELECT 
                        gb.user_id,
                        gb.parent_id,
                        0 AS `level`,
                        gb.created_at,
                        CONCAT(gb.user_id) path
                    FROM cm_genealogy_binary AS gb
                    WHERE gb.parent_id = ?
                    
                    UNION ALL
                    
                    SELECT
                        gb.user_id,
                        gb.parent_id,
                        d.`level` + 1 `level`,
                        gb.created_at,
                        CONCAT(d.path, ',', gb.user_id) path
                    FROM cm_genealogy_binary AS gb
                    JOIN downline AS d ON d.user_id = gb.parent_id
                )
                SELECT 1 FROM downline d WHERE d.user_id = users.id
            )", [$this->id])->where("id", $user_id)->count(DB::raw("1")) > 0;
    }

    public function generateRandomPassword($char_limit)
    {
        $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz!@$%&*#';
        return substr(str_shuffle($data), 0, $char_limit);
    }

    public function updateExistingRecord()
    {
        $users = DB::table("users AS u")
        ->leftjoin("mba_ft_individual AS i", "u.migrated_user_id", "=", "i.id")
        ->leftjoin("mba_oc_customer AS c", "i.oc_customer_ref_id", "=", "c.customer_id")
        ->whereRaw("EXISTS(SELECT 1 FROM users u WHERE u.migrated_user_id = i.id)")
        ->selectRaw("
            u.id as user_id,
            0 AS sponsorid,
            IFNULL(i.user_name, CONCAT(c.firstname, i.id)) AS site,
            'password123' AS password,
            'Yes' AS active,
            IFNULL(c.firstname, '') AS fname,
            IFNULL(c.lastname, '') AS lname,
            '' AS address,
            '' AS city,
            '' AS state,
            '' AS zip,
            '' AS country,
            c.email AS email,
            c.telephone AS dayphone,
            c.telephone AS evephone,
            3 AS levelid,
            c.telephone AS cellphone,
            i.id AS memberid,
            i.id AS migrated_user_id,
            IFNULL(i.sponsor_id, 0) AS sponmemberid,
            IFNULL(i.sponsor_id, 0) AS migrated_sponsor_id,
            'migrated' AS referurl,
            i.date_of_joining AS enrolled_date,
            NOW() AS created

        ")->get();


        foreach($users as $i => $user) {
            $user_id = $user->user_id;
            $site = $user->site;
            $fname = $user->fname;
            $lname = $user->lname;
            $email = $user->email;
            $dayphone = $user->dayphone;
            $evephone = $user->evephone;
            $cellphone = $user->cellphone;
            $memberid = $user->memberid;
            $migrated_user_id = $user->migrated_user_id;
            $sponmemberid = $user->sponmemberid;
            $migrated_sponsor_id = $user->migrated_sponsor_id;
            $enrolled_date = $user->enrolled_date;
            $created = $user->created;

            DB::table("users")
                ->where("id", $user_id)
                ->update([
                    "sponsorid" => 0,
                    "site" => $site,
                    "password" => 'password123',
                    "active" => 'Yes',
                    "fname" => $fname,
                    "lname" => $lname,
                    "address" => '',
                    "city" => '',
                    "state" => '',
                    "zip" => '',
                    "country" => '',
                    "email" => $email,
                    "dayphone" => $dayphone,
                    "evephone" => $evephone,
                    "levelid" => 3,
                    "cellphone" => $cellphone,
                    "memberid" => $memberid,
                    "migrated_user_id" => $migrated_user_id,
                    "sponmemberid" => $sponmemberid,
                    "migrated_sponsor_id" => $migrated_sponsor_id,
                    "referurl" => 'migrated',
                    "enrolled_date" => $enrolled_date,
                    "created" => $created
                ]);
        }
    }
}
