<?php


namespace Commissions\Member;


use App\User;
use App\UserMatrix;
use App\UserMatrixDeleted;
use Carbon\Carbon;
use Commissions\Console;
use Illuminate\Support\Facades\DB;
use PDO;

class MatrixTree extends Console
{
    const ROOT_ID = 3;

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();

    }

    public function getBreadcrumb($root_id, $user_id)
    {
        $sql = "
            WITH RECURSIVE upline (user_id, parent_id, `level`) AS (
                SELECT
                    user_id,
                    parent_id,
                    0 AS `level`
                FROM cm_genealogy_matrix
                WHERE user_id = :user_id AND :root_id <> :user_id1
                
                UNION ALL
                
                SELECT
                    u.user_id,
                  u.parent_id,
                  upline.`level` + 1 `level`
                FROM cm_genealogy_matrix u
                INNER JOIN upline ON upline.parent_id = u.user_id
                WHERE u.user_id <> :root_id1
            )
            SELECT
                up.user_id,
                CONCAT(u.fname, ' ', u.lname) `name`,
                up.`level`
            FROM upline up
            JOIN users u ON u.id = up.user_id
            ORDER BY up.`level` DESC;   
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_id1', $user_id);
        $stmt->bindParam(':root_id', $root_id);
        $stmt->bindParam(':root_id1', $root_id);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDownlines($user_id)
    {
        $customers = config('commission.member-types.customers');
        $affiliates = config('commission.member-types.affiliates');
        $default_affiliate = config('commission.affiliate');

        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`, created_at, path) AS (
                SELECT 
                    gm.user_id,
                    gm.parent_id,
                    0 AS `level`,
                    gm.created_at,
                    CONCAT(gm.user_id) path
                FROM cm_genealogy_matrix AS gm
                WHERE gm.user_id = :user_id
                
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
            SELECT
                d.user_id,
                d.parent_id,
                d.level,
                d.created_at,
                d.path,
                
                CONCAT(u.fname, ' ', u.lname) AS `member`,
                EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :customers)) is_customer,
                IFNULL(r.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS paid_as_rank,
                IFNULL(c.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS current_rank,
                IFNULL(a.name, IF(EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$customers')), 'Customer', '$default_affiliate')) AS highest_rank,
                
                IFNULL(ROUND(dv.coach_points, 2), 0) AS coach_points,
                IFNULL(ROUND(dv.referral_points, 2), 0) AS referral_points,
                IFNULL(ROUND(dv.organization_points, 2), 0) AS organization_points,
                IFNULL(ROUND(dv.team_group_points, 2), 0) AS team_group_points,
                
                EXISTS(
                    SELECT 1
                    FROM v_cm_transactions t 
                    WHERE t.user_id = u.id 
                        AND t.type = 'product'
                        AND t.transaction_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()
                ) has_order_last_30_days,
                DATE_FORMAT(u.created, '%Y-%m-%d') enrolled_date,
                
                dv.level,
                dr.rank_id,
                dr.is_active,
                dr.min_rank_id,
                dr.paid_as_rank_id,
                dr.rank_date
            FROM downline d
            JOIN users u ON u.id = d.user_id
            LEFT JOIN cm_daily_volumes dv ON dv.user_id = d.user_id AND dv.volume_date = CURRENT_DATE()
            LEFT JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
            LEFT JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
            LEFT JOIN cm_ranks c ON c.id = dr.rank_id
            LEFT JOIN cm_achieved_ranks ac ON ac.user_id = u.id AND ac.rank_id = (
                SELECT aac.rank_id
                FROM cm_achieved_ranks aac
                WHERE aac.user_id = ac.user_id
                ORDER BY aac.rank_id DESC LIMIT 1
            )
            LEFT JOIN cm_ranks a ON a.id = ac.rank_id
            LEFT JOIN cm_ranks n ON n.id = dr.rank_id + 1
            WHERE d.level < 3
            ORDER BY d.`level` ASC, d.created_at ASC, d.user_id ASC   
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':customers', $customers);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getUnplacedUsers()
    {
        $sql = "
            SELECT
                u.id AS user_id,
                u.sponsorid AS parent_id
            FROM users u
            WHERE
                u.levelid = 3
                AND EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, :affiliates))
                AND NOT EXISTS(SELECT 1 FROM cm_genealogy_matrix gm WHERE gm.user_id = u.id)
                /*AND NOT EXISTS (
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
                    SELECT 1 FROM downline d WHERE d.user_id = u.id
                )
                AND u.id NOT IN(
                    SELECT userid FROM transactions WHERE ccnumber IN (
                       '0d8d77110d411737bbd1d1fe29339917', 
                       '995b26f3e0449194a7c80aa634b97e89',
                       '1f947ae724d87f02a7c80aa634b97e89', 
                       '5d5a03b44291c15f0ac08fd5eabd6e33', 
                       'cb89310be6889274f6cb7f517fd68c64', 
                       'd5762d6e796a04cf737146e1a280f070',
                       '933571e67c253848b8235702e9ab966a',
                       '21517a855caf03bed69304fbd48dd1b32f25e73b968fda01',
                       'd1d379268a53a2ca389b41adc5122814',
                       '0d8d77110d411737809d53b33669e6a0'
                    )
                )*/
            ORDER BY u.id;
        ";
        $affiliates = config('commission.member-types.affiliates');
        $smt = $this->db->prepare($sql);
        $smt->bindParam(':affiliates', $affiliates);
        $smt->execute();
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function removeDeleteUsersFromMatrixTree()
    {
        $users = $this->getDeleteUsers();

        foreach($users as $user) {
            $user_id = $user['user_id'];
            $downline = $this->getDownline($user_id);
            UserMatrix::where('user_id', $user_id)->delete();
            UserMatrixDeleted::create($user);

            foreach ($downline as $d) {

                UserMatrix::where('user_id', $d['user_id'])->delete();
                $d['upline_id'] = $user_id;
                UserMatrixDeleted::create($d);

            }
        }
    }

    private function getDeleteUsers()
    {
        $sql = "
            SELECT
                m.user_id, m.parent_id, m.created_at placed_at
            FROM cm_genealogy_matrix m
            LEFT JOIN users u ON u.id = m.user_id
            WHERE u.id IS NULL
            ORDER BY m.created_at ASC;
        ";
        $smt = $this->db->prepare($sql);
        $smt->execute();
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDownline($user_id)
    {
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`, created_at) AS (
                SELECT 
                    gm.user_id,
                    gm.parent_id,
                    0 AS `level`,
                    gm.created_at
                FROM cm_genealogy_matrix AS gm
                WHERE gm.user_id = $user_id
                
                UNION ALL
                
                SELECT
                    gm.user_id,
                    gm.parent_id,
                    d.`level` + 1 `level`,
                    gm.created_at
                FROM cm_genealogy_matrix AS gm
                JOIN downline AS d ON d.user_id = gm.parent_id
            )
            SELECT
                d.user_id,
                d.parent_id,
                d.created_at placed_at
            FROM downline d
        ";
        $smt = $this->db->prepare($sql);
        $smt->execute();
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function process()
    {
        DB::transaction(function(){

            $this->removeDeleteUsersFromMatrixTree();

            $users = $this->getUnplacedUsers();

            foreach($users as $user) {
                $this->place($user['user_id'], $user['parent_id']);
            }

        });
    }

    /**
     * @param int $user_id User ID
     * @param int|null $enroller_parent_id User's Parent ID in users table
     */
    public function place($user_id, $enroller_parent_id = null)
    {
        $enroller_parent_id = +$this->getFirstAffiliateUpline($user_id);

        if($enroller_parent_id === 0 && +$user_id !== static::ROOT_ID) {
            $this->log("Skipping User ID {$user_id}. No upline affiliate.");
            return;
        }

        $parent_id = +$this->getMatrixParentID($enroller_parent_id);

        if($parent_id === 0 && +$user_id !== static::ROOT_ID) {
            $this->log("Skipping User ID {$user_id}. Parent ID {$enroller_parent_id} is not in the matrix tree.");
            return;
        }

        $created_at = Carbon::now()->addSeconds($user_id)->toAtomString();
        UserMatrix::create(compact('user_id','parent_id', 'created_at'));
        // UserMatrix::find($parent_id)->increment('leg_count');
    }

    private function getFirstAffiliateUpline($user_id)
    {
        $sql = "
            WITH RECURSIVE upline (user_id, parent_id, `level`) AS (
                SELECT
                    id AS user_id,
                    sponsorid AS parent_id,
                    1 AS `level`
                FROM users
                WHERE id = :user_id
                
                UNION ALL
                
                SELECT
                    u.id AS user_id,
                  u.sponsorid AS parent_id,
                  upline.`level` + 1 `level`
                FROM users u
                INNER JOIN upline ON upline.parent_id = u.id
            )
            SELECT 
                u.parent_id AS user_id
            FROM upline u 
            WHERE EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.parent_id AND FIND_IN_SET(cm.catid, :affiliates))
            LIMIT 1;
        ";

        $affiliates = config('commission.member-types.affiliates');

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':user_id', $user_id);
        $smt->bindParam(':affiliates', $affiliates);
        $smt->execute();
        return $smt->fetchColumn();
    }

    private function getMatrixParentID($parent_id)
    {
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`, leg_count, created_at) AS (
                SELECT 
                    gm.user_id,
                    gm.parent_id,
                    0 AS `level`,
                    (SELECT COUNT(1) FROM cm_genealogy_matrix AS s JOIN users u ON u.id = s.user_id WHERE s.parent_id = gm.user_id) leg_count,
                    gm.created_at
                FROM cm_genealogy_matrix AS gm
                WHERE gm.user_id = :parent_id
                
                UNION ALL
                
                SELECT
                    gm.user_id,
                    gm.parent_id,
                    d.`level` + 1 `level`,
                    (SELECT COUNT(1) FROM cm_genealogy_matrix AS s JOIN users u ON u.id = s.user_id WHERE s.parent_id = gm.user_id) leg_count,
                    gm.created_at
                FROM cm_genealogy_matrix AS gm
                JOIN downline AS d ON d.user_id = gm.parent_id
            )
            SELECT
                d.user_id
            FROM downline d
            JOIN users u ON u.id = d.user_id
            WHERE d.leg_count < 4
            ORDER BY d.`level` ASC, d.leg_count ASC, d.created_at ASC
            LIMIT 1;
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':parent_id', $parent_id);
        $smt->execute();
        return $smt->fetchColumn();
    }

}