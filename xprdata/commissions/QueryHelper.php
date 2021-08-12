<?php


namespace Commissions;


class QueryHelper
{
    public static function NotExistsUnderBen($column)
    {
        return "
            NOT EXISTS (
                WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                    SELECT 
                        id AS user_id,
                        sponsorid AS parent_id,
                        1 AS `level`
                    FROM users
                    WHERE id = 20
                    
                    UNION ALL
                    
                    SELECT
                        p.id AS user_id,
                        p.sponsorid AS parent_id,
                        downline.`level` + 1 `level`
                    FROM users p
                    INNER JOIN downline ON p.sponsorid = downline.user_id AND p.levelid = 3
                )
                SELECT 1 FROM downline d WHERE d.user_id = $column
             )
        ";
    }
}