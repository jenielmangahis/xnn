<?php


namespace Commissions\Common;


use Illuminate\Support\Facades\DB;

class Autocomplete
{
    const RESULT_LIMIT = 10;

    public function getEnrollerDownline($member_id, $search, $page = 0)
    {
        $query = DB::table('users AS u')
            ->selectRaw("
                u.id,
                CONCAT('#', u.id, ': ', u.fname, ' ', u.lname, ' (', u.site, ')') AS text
            ")
            ->whereRaw("EXISTS(
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
                SELECT 1 FROM downline d WHERE d.user_id = u.id
            )", [$member_id]
            )->orderBy("u.id");

        if(is_numeric($search) && is_int(+$search)) {
            $query->where('u.id', $search);
        } elseif(!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('u.site', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT('#', u.id, ': ', u.fname, ' ', u.lname) LIKE ?", ["%{$search}%"]);
            });
        }

        $count_filtered = $query->count(DB::raw("1"));

        $results = $query->skip($page)->take(static::RESULT_LIMIT)->get();

        return [
            'results' => $results,
            'pagination' => [
                'more' => (($page + 1) * static::RESULT_LIMIT) < $count_filtered
            ]
        ];
    }

    public function getPlacementDownline($member_id, $search, $page = 0)
    {
        $affiliates = config('commission.member-types.affiliates');

        $query = DB::table('users AS u')
            ->selectRaw("
                u.id,
                CONCAT('#', u.id, ': ', u.fname, ' ', u.lname, ' (', u.site, ')') AS text
            ")
            ->whereRaw("EXISTS(
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
                SELECT 1 FROM downline d WHERE d.user_id = u.id
            )", [$member_id]
            )->orderBy("u.id");

        if(is_numeric($search) && is_int(+$search)) {
            $query->where('u.id', $search);
        } elseif(!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('u.site', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT('#', u.id, ': ', u.fname, ' ', u.lname) LIKE ?", ["%{$search}%"]);
            });
        }

        $count_filtered = $query->count(DB::raw("1"));

        $results = $query->skip($page)->take(static::RESULT_LIMIT)->get();

        return [
            'results' => $results,
            'pagination' => [
                'more' => (($page + 1) * static::RESULT_LIMIT) < $count_filtered
            ]
        ];
    }

    public function getMatrixDownline($member_id, $search, $page = 0)
    {
        $affiliates = config('commission.member-types.affiliates');

        $query = DB::table('users AS u')
            ->selectRaw("
                u.id,
                CONCAT('#', u.id, ': ', u.fname, ' ', u.lname, ' (', u.site, ')') AS text
            ")
            ->whereRaw("EXISTS(
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
                SELECT 1 FROM downline d WHERE d.user_id = u.id
            )", [$member_id]
            )->orderBy("u.id");

        if(is_numeric($search) && is_int(+$search)) {
            $query->where('u.id', $search);
        } elseif(!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('u.site', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT('#', u.id, ': ', u.fname, ' ', u.lname) LIKE ?", ["%{$search}%"]);
            });
        }

        $count_filtered = $query->count(DB::raw("1"));

        $results = $query->skip($page)->take(static::RESULT_LIMIT)->get();

        return [
            'results' => $results,
            'pagination' => [
                'more' => (($page + 1) * static::RESULT_LIMIT) < $count_filtered
            ]
        ];
    }

    public function getMembers($search, $page = 0)
    {
        $query = DB::table('users AS u')
            ->selectRaw("
                u.id,
                CONCAT('#', u.id, ': ', u.fname, ' ', u.lname, ' (', u.site, ')') AS text
            ")
            ->where("u.levelid", 3)
            ->where("u.fname", "<>", "")
            ->orderBy("u.id");

        if(is_numeric($search) && is_int(+$search)) {
            $query->where('u.id', $search);
        } elseif(!!$search) {            
            $query->where(function ($query) use ($search) {                
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    /*->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('u.site', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT('#', u.id, ': ', u.fname, ' ', u.lname) LIKE ?", ["%{$search}%"])*/;
            });
        }

        $count_filtered = $query->count(DB::raw("1"));

        if($page > 0) {
            $take = static::RESULT_LIMIT * ($page - 1);
            $results = $query->skip($take)->take(static::RESULT_LIMIT)->get();
        } else {
            $results = $query->skip($page)->take(static::RESULT_LIMIT)->get();
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => (($page + 1) * static::RESULT_LIMIT) < $count_filtered
            ]
        ];
    }

    public function getAffiliates($search, $page = 0)
    {
        $query = DB::table('users AS u')
            ->selectRaw("
                u.id,
                CONCAT('#', u.id, ': ', u.fname, ' ', u.lname, ' (', u.site, ')') AS text
            ")
            ->join("cm_affiliates AS a", "a.user_id", "=", "u.id")
            ->where("u.levelid", 3)
            ->orderBy("u.id");

        if(is_numeric($search) && is_int(+$search)) {
            $query->where('u.id', $search);
        } elseif(!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")
                    ->orWhere('u.site', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT('#', u.id, ': ', u.fname, ' ', u.lname) LIKE ?", ["%{$search}%"]);
            });
        }

        $count_filtered = $query->count(DB::raw("1"));

        $results = $query->skip($page)->take(static::RESULT_LIMIT)->get();

        return [
            'results' => $results,
            'pagination' => [
                'more' => (($page + 1) * static::RESULT_LIMIT) < $count_filtered
            ]
        ];
    }
}