<?php

namespace Commissions\Admin;
use Illuminate\Support\Facades\DB;
use PDO;

class UplineReport 
{
    protected $db;

    public function getUplines($member_id, $tree_type)
    {

        $reports = [];

        $members = [];

        if(+$tree_type === 1) { // enroller
            $members = $this->getEnrollmentTreeList($member_id);
        } else if(+$tree_type === 2) { // placement
            $members = $this->getPlacementTreeList($member_id);
        } else if(+$tree_type === 3) { // matrix
            $members = $this->getMatrixTreeList($member_id);
        }

        foreach ($members as $member) {
            $rank = $this->getRankDetails($member_id);

            $reports[] = array(
                'member_id' => $member['id'],
                'member' => $member['member_name'],
                'level' => $member['level'],
                'rank' => empty($member['paid_as_rank_name']) ? $rank['rank_details'] : $member['paid_as_rank_name'],
                'sponsor_id' => $member['sponsorid'],
                'sponsor' => $member['sponsor_name']
            );
        }

        return $reports;
    }

    private function getEnrollmentTreeList($member_id) 
    {
        $db = DB::connection()->getPdo();
        $sql = "WITH RECURSIVE enrollment_tree AS (
                    SELECT
                        u.id,
                        CONCAT(u.fname, ' ', u.lname) AS member_name,
                        u.sponsorid,
                        1 AS level,
                        (SELECT CONCAT(fname, ' ', lname) FROM users WHERE id = su.sponsorid) AS sponsor_name  
                    FROM
                        users AS u
                    LEFT JOIN (
                        SELECT id, sponsorid FROM users
                    ) AS su ON su.id = u.id
                    WHERE
                        u.id = :user_id
                        AND u.levelid = 3
                        
                    UNION ALL
                    
                    SELECT
                        ux.id,
                        CONCAT(ux.fname, ' ', ux.lname) AS member_name,
                        ux.sponsorid,
                        t.level + 1,
                        (SELECT CONCAT(fname, ' ', lname) FROM users WHERE id = ux.sponsorid) AS sponsor_name  
                    FROM
                        users ux
                        JOIN enrollment_tree t ON t.sponsorid = ux.id
                    WHERE
                        ux.levelid = 3
                )
                SELECT
                    et.id,
                    et.member_name,
                    et.level,
                    et.sponsorid,
                    et.sponsor_name,
                    r.name AS paid_as_rank_name
                FROM
                    enrollment_tree et
                    LEFT JOIN cm_daily_ranks dr ON dr.user_id = et.id AND dr.rank_date = CURRENT_DATE()
                    LEFT JOIN cm_ranks r ON r.id = dr.paid_as_rank_id";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(":user_id", $member_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPlacementTreeList($member_id)
    {
        $db = DB::connection()->getPdo();

        $affiliates = config('commission.member-types.affiliates');

        $sql = "
            WITH RECURSIVE upline (user_id, parent_id, `level`) AS (
                SELECT
                    p.user_id,
                    p.sponsor_id AS parent_id,
                    1 AS `level`
                FROM cm_genealogy_placement p
                JOIN users u ON u.id = p.user_id
                WHERE p.user_id = :user_id AND u.levelid = 3
                    AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
                
                UNION ALL
                
                SELECT
                    p.user_id,
                    p.sponsor_id AS parent_id,
                    upline.`level` + 1 `level`
                FROM cm_genealogy_placement p
                JOIN upline ON upline.parent_id = p.user_id
                JOIN users u ON u.id = p.user_id
                WHERE u.levelid = 3
                    AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = p.user_id AND FIND_IN_SET(cm.catid, '$affiliates'))
            )
            SELECT
                up.user_id AS id,
                CONCAT(u.fname, ' ', u.lname) member_name,
                up.level,
                up.parent_id AS sponsorid,
                IF(s.id IS NOT NULL, CONCAT(s.fname, ' ', s.lname), NULL) sponsor_name
            FROM upline AS up
            JOIN users u ON u.id = up.user_id
            LEFT JOIN users s ON s.id = up.parent_id
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(":user_id", $member_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMatrixTreeList($member_id)
    {
        $db = DB::connection()->getPdo();

        $affiliates = config('commission.member-types.affiliates');

        $sql = "
            WITH RECURSIVE upline (user_id, parent_id, `level`) AS (
                SELECT
                    p.user_id,
                    p.parent_id,
                    1 AS `level`
                FROM cm_genealogy_matrix p
                WHERE p.user_id = :user_id 
                
                UNION ALL
                
                SELECT
                    p.user_id,
                    p.parent_id,
                    upline.`level` + 1 `level`
                FROM cm_genealogy_matrix p
                JOIN upline ON upline.parent_id = p.user_id
            )
            SELECT
                up.user_id AS id,
                CONCAT(u.fname, ' ', u.lname) member_name,
                up.level,
                up.parent_id AS sponsorid,
                IF(s.id IS NOT NULL, CONCAT(s.fname, ' ', s.lname), 'Root') sponsor_name
            FROM upline AS up
            JOIN users u ON u.id = up.user_id
            LEFT JOIN users s ON s.id = up.parent_id;
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(":user_id", $member_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getRankDetails($member_id) 
    {
        $db = DB::connection()->getPdo();
        $end_date = date("Y-m-d");
        $start_date = date('Y-m-d', strtotime('-30 days', strtotime($end_date)));

        $sql = "SELECT c.description AS rank_details, a.rank_date
                FROM cm_daily_ranks a
                LEFT JOIN cm_daily_volumes b ON a.volume_id = b.id
                JOIN cm_ranks c ON a.paid_as_rank_id = c.id
                JOIN users d ON a.user_id = d.id
                WHERE d.id = :member_id
                    AND a.rank_date BETWEEN :start_date AND :end_date
                ORDER BY c.id DESC
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(":member_id", $member_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $result;
    }
}