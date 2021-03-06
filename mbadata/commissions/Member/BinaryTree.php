<?php


namespace Commissions\Member;


use App\DailyVolume;
use App\User;
use App\UserBinary;
use App\UserMatrix;
use App\UserMatrixDeleted;
use App\UserMod;
use Carbon\Carbon;
use Commissions\Console;
use Commissions\Exceptions\AlertException;
use Illuminate\Support\Facades\DB;
use PDO;

class BinaryTree extends Console
{
    const IS_TEST = false;
    const MAX_LEVEL = 5; // starts at level 0

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
                FROM cm_genealogy_binary
                WHERE user_id = :user_id AND :root_id <> :user_id1
                
                UNION ALL
                
                SELECT
                    u.user_id,
                  u.parent_id,
                  upline.`level` + 1 `level`
                FROM cm_genealogy_binary u
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

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_id1', $user_id);
        $stmt->bindParam(':root_id', $root_id);
        $stmt->bindParam(':root_id1', $root_id);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDownlines($user_id)
    {

        $default_affiliate = config('commission.affiliate');
        $affiliates = config('commission.member-types.affiliates');
        $maxLevel = static::MAX_LEVEL;

        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`, created_at, path, position) AS (
                SELECT 
                    gm.user_id,
                    gm.parent_id,
                    0 AS `level`,
                    gm.created_at,
                    CONCAT(gm.user_id) path,
                    gm.position
                FROM cm_genealogy_binary AS gm
                WHERE gm.user_id = :user_id
                
                UNION ALL
                
                SELECT
                    gm.user_id,
                    gm.parent_id,
                    d.`level` + 1 `level`,
                    gm.created_at,
                    CONCAT(d.path, ',', gm.user_id) path,
                    gm.position
                FROM cm_genealogy_binary AS gm
                JOIN downline AS d ON d.user_id = gm.parent_id
            )
            SELECT
                d.user_id,
                d.parent_id,
                d.level,
                d.created_at,
                d.path,
                d.position,
                CONCAT(u.fname, ' ', u.lname) AS `member`,
                CONCAT(s.fname, ' ', s.lname) AS `sponsor`,
                u.enrolled_date,

                IF(
                    EXISTS(SELECT 1 FROM categorymap cm WHERE cm.userid = u.id AND FIND_IN_SET(cm.catid, '$affiliates')),
                    IF(
                        r.name = 'Affiliate', 'IBO',
                        IFNULL(r.name, 'IBO')
                    ), 
                    'Customer'
                ) AS paid_as_rank,
                IFNULL(dv.pv, 0) AS pv,
                dr.is_active,
                dr.paid_as_rank_id,
                0 is_empty
            FROM downline d
            JOIN users u ON u.id = d.user_id
            LEFT JOIN users s ON s.id = u.sponsorid
            LEFT JOIN cm_daily_volumes dv ON dv.user_id = d.user_id AND dv.volume_date = CURRENT_DATE()
            LEFT JOIN cm_daily_ranks dr ON dr.volume_id = dv.id
            LEFT JOIN cm_ranks r ON r.id = dr.paid_as_rank_id
            WHERE d.level <= $maxLevel
            ORDER BY d.`level` ASC, d.position ASC, d.created_at ASC, d.user_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $this->insertEmptyLegs($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    protected function insertEmptyLegs($users)
    {
        $downline = [];

        $users = collect($users);

        foreach ($users as $user) {

            $user_id = $user['user_id'];
            $level = $user['level'];

            $user['is_empty'] = 0;

            $downline[] = $user;

			
            if ($user['level'] >= static::MAX_LEVEL) {
                continue;
            }
			

            $left = $users->where("parent_id", $user['user_id'])->where("position", UserBinary::POSITION_LEFT_LEG)->first();
            $right = $users->where("parent_id", $user['user_id'])->where("position", UserBinary::POSITION_RIGHT_LEG)->first();

            if ($left === null) {
                $downline[] = [
                    'user_id' => time(),
                    'parent_id' => $user_id,
                    'position' => UserBinary::POSITION_LEFT_LEG,
                    'level' => $level + 1,
                    'is_empty' => 1,
                ];
            }

            if ($right === null) {
                $downline[] = [
                    'user_id' => time(),
                    'parent_id' => $user_id,
                    'position' => UserBinary::POSITION_RIGHT_LEG,
                    'level' => $level + 1,
                    'is_empty' => 1,
                ];
            }
        }

        $levels = array_column($downline, 'level');
        $positions = array_column($downline, 'position');

        array_multisort($levels, SORT_ASC, $positions, SORT_ASC, $downline);

        return $downline;
    }

    public function getUserDetails($user_id)
    {
        $volumes = DailyVolume::ofMember($user_id)->today()->firstOrNew([
            'user_id' => $user_id
        ]);

        $binary = UserBinary::findOrFail($user_id);

        $carry_over_volume_left = +$volumes->rollover_volume_left;
        $current_group_volume_left = +$volumes->group_volume_left_leg;
        $total_group_volume_left = +$volumes->total_group_volume_left_leg;

        $carry_over_volume_right = +$volumes->rollover_volume_right;
        $current_group_volume_right = +$volumes->group_volume_right_leg;
        $total_group_volume_right = +$volumes->total_group_volume_right_leg;

        $parent_id = $binary->parent_id;
        $placement_preference = $binary->placement_preference;
        $level = +$volumes->level;

        return compact(
            'user_id',
            'parent_id',
            'level',
            'placement_preference',
            'carry_over_volume_left',
            'current_group_volume_left',
            'total_group_volume_left',
            'carry_over_volume_right',
            'current_group_volume_right',
            'total_group_volume_right'
        );
    }

    public function setPlacementPreference($user_id, $placement_preference)
    {
        $binary = UserBinary::findOrFail($user_id);

        if (!in_array($placement_preference, [UserBinary::PREFERENCE_LESSER_VOLUME_LEG, UserBinary::PREFERENCE_RIGHT_LEG, UserBinary::PREFERENCE_LEFT_LEG]))
            throw new AlertException("Preference not found.");

        return DB::transaction(function () use ($user_id, $binary, $placement_preference) {

            UserMod::newRecordFor($user_id)
                ->setFromModule(UserMod::MODULE_BINARY_TREE)
                ->setChanges("Placement Preference", $binary->placement_preference, $placement_preference)
                ->save();

            $binary->placement_preference = $placement_preference;
            $binary->save();
            return $binary;
        });

    }

    public function process()
    {
        DB::transaction(function () {

            if (static::IS_TEST) $this->deleteBinaryTree();

            $users = $this->getUnplacedUsers();

            foreach ($users as $user) {

                if (!static::IS_TEST && strpos(strtoupper(trim($user['last_name'])), "NAXUMTEST") !== false) {
                    $this->log("Skipping Test User ID {$user['user_id']}.");
                    continue;
                }

                $this->place($user['user_id'], $user['parent_id']);
            }

        });
    }

     public function getBinarySponsorPlacement($parent_id)
     {
        $sql = "
            SELECT * FROM cm_genealogy_binary WHERE user_id = $parent_id
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
        $sponsor = $smt->fetchAll(PDO::FETCH_ASSOC);

		if ($sponsor) {
			return $sponsor[0];
		}

		return false;
     }

     public function getSponsorLastDownline($user_id, $leg)
     {
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`, created_at, path, `position`) AS (
                SELECT 
                    gm.user_id,
                    gm.parent_id,
                    0 AS `level`,
                    gm.created_at,
                    CONCAT(gm.user_id) path,
                    gm.position
                FROM cm_genealogy_binary AS gm
                WHERE gm.user_id = $user_id
                
                UNION ALL
                
                SELECT
                    gm.user_id,
                    gm.parent_id,
                    d.`level` + 1 `level`,
                    gm.created_at,
                    CONCAT(d.path, ',', gm.user_id) path,
                    gm.position
                FROM cm_genealogy_binary AS gm
                JOIN downline AS d ON d.user_id = gm.parent_id
                WHERE gm.position = $leg
            )
            SELECT
                d.user_id,
                d.parent_id,
                d.level,
                d.created_at,
                d.path,
                d.position
            FROM downline d
            ORDER BY d.`level` DESC, d.position DESC, d.created_at DESC, d.user_id DESC
            LIMIT 1
        ";

        $smt = $this->db->prepare($sql);
        $smt->execute();
        $sponsor = $smt->fetchAll(PDO::FETCH_ASSOC);

        return $sponsor;
     }

    public function place($user_id, $parent_id)
    {
        
        // get enrollment sponsor details of new user
        $sponsor_datails = $this->getBinarySponsorPlacement($parent_id);

        if($sponsor_datails) {

            $leg = $this->getPreferredLeg($sponsor_datails['user_id'], $sponsor_datails['placement_preference']);

            $last_downline = $this->getSponsorLastDownline($sponsor_datails['user_id'], $leg);
            
            foreach($last_downline as $downline) {

                    $user = new UserBinary();
                    $user->user_id = $user_id;
                    $user->parent_id = $downline['user_id'];
                    $user->position = $leg;
                    $user->placement_preference = UserBinary::PREFERENCE_LESSER_VOLUME_LEG;
                    $user->save();

                    $this->log("User ID: $user_id Parent ID: $user->parent_id Position: $user->position");
            }
        } else {
            $leg = $this->getPreferredLeg($user_id, '');

            $user = new UserBinary();
            $user->user_id = $user_id;
            $user->parent_id = 0;
            $user->position = $leg;
            $user->placement_preference = UserBinary::PREFERENCE_LESSER_VOLUME_LEG;
            $user->save();

            $this->log("User ID: $user_id Parent ID: $user->parent_id Position: $user->position");
        }
    }

    public function changeSponsor($user_id, $parent_id, $leg_position)
    {
        $parent = UserBinary::find($parent_id);

        if ($parent === null) throw new \Exception("Parent not found.");

        $user = UserBinary::find($user_id);

        if ($user === null) throw new \Exception("User not found.");

        if ($this->isCircularSponsorship($user_id, $parent_id)) throw new \Exception("There is a circular sponsorship.");

        // Check parent leg vacancies
        $parent = UserBinary::with('legs')->find($parent_id);
        $leg_user = $parent->legs->where("position", $leg_position)->first();

        if ($leg_user !== null) throw new \Exception("Leg position not vacant.");

        $user->parent_id = $parent_id;
        $user->position = $leg_position;
        $user->save();

        return $user;
    }

    public function isCircularSponsorship($user_id, $parent_id)
    {
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, dt_enrolled, `level`) AS (
                SELECT 
                    user_id AS userid,
                    parent_id,
                    created_at,                    
                    1 AS `level`
                FROM cm_genealogy_binary
                WHERE parent_id = :user_id  
                
                UNION ALL
                
                SELECT
                    gb.user_id,
                    gb.parent_id,
                    gb.created_at,
                    d.`level` + 1 `level`
                FROM cm_genealogy_binary gb
                INNER JOIN downline AS d ON gb.parent_id = d.user_id
            )
            SELECT COUNT(1) FROM downline AS d WHERE d.user_id = :parent_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam('user_id', $user_id);
        $stmt->bindParam('parent_id', $parent_id);
        $stmt->execute();
        return +$stmt->fetchColumn() > 0;
    }

    protected function getUnplacedUsers()
    {

        $sql = "
            SELECT
                u.id AS user_id,
                u.sponsorid AS parent_id,
                u.lname AS last_name
            FROM users u
            WHERE
                u.levelid = 3 AND active='Yes'
                AND NOT EXISTS(SELECT 1 FROM cm_genealogy_binary gb WHERE gb.user_id = u.id)
                AND u.lname NOT LIKE '%naxumtest%' -- case-insensitive
                AND u.id > 13 
                AND u.sponsorid != 0
            ORDER BY u.id
        ";

        if (static::IS_TEST) {
            $sql = "
                SELECT
                    u.id AS user_id,
                    u.sponsorid AS parent_id,
                    u.lname AS last_name
                FROM users u
                JOIN cm_affiliates a ON a.user_id = u.id
                LEFT JOIN v_cm_transactions t ON t.user_id = a.user_id
                    AND t.transaction_id = (
                        SELECT tt.transaction_id 
                        FROM v_cm_transactions tt
                        WHERE tt.user_id = t.user_id AND tt.`type` = 'product' 
                        ORDER BY tt.transaction_date ASC
                        LIMIT 1
                    )
                WHERE
                    u.levelid = 3
                    AND u.id <> 13
                    AND NOT EXISTS(SELECT 1 FROM cm_genealogy_binary gb WHERE gb.user_id = u.id)
                ORDER BY t.transaction_date, a.affiliated_at, a.user_id
                LIMIT 50
            ";
        }

        $smt = $this->db->prepare($sql);
        $smt->execute();
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function deleteBinaryTree()
    {
        if (!static::IS_TEST) throw new \Exception("Not allowed.");

        $sql = "
            DELETE FROM cm_genealogy_binary WHERE user_id NOT IN(3, 20, 988);
        ";
        $smt = $this->db->prepare($sql);
        $smt->execute();
    }

    public function getBinaryParent($parent_id, &$depth = 0)
    {
        if (++$depth > 100000) throw new \Exception("Max Recursion");

        $parent = UserBinary::with('legs')->find($parent_id);

        $leg = $this->getPreferredLeg($parent_id, $parent->placement_preference);

        $leg_user = $parent->legs->where("position", $leg)->first();

        if ($leg_user !== null) return $this->getBinaryParent($leg_user->user_id, $depth);
        
        $placement_preference = $parent->placement_preference;

        return compact('parent_id', 'leg', 'placement_preference');
    }

    protected function getPreferredLeg($user_id, $placement_preference = null)
    {
        if ($placement_preference === UserBinary::PREFERENCE_LEFT_LEG) return UserBinary::POSITION_LEFT_LEG;
        if ($placement_preference === UserBinary::PREFERENCE_RIGHT_LEG) return UserBinary::POSITION_RIGHT_LEG;

        $volumes = DailyVolume::ofMember($user_id)->today()->first();

        if ($volumes === null) return UserBinary::POSITION_LEFT_LEG;

        if (static::IS_TEST) {
            $min = 1000;
            $max = 2000;
            $volumes->total_group_volume_left = mt_rand($min * 10, $max * 10) / 10;
            $volumes->total_group_volume_right = mt_rand($min * 10, $max * 10) / 10;
        }

        return $volumes->total_group_volume_left <= $volumes->total_group_volume_right ? UserBinary::POSITION_LEFT_LEG : UserBinary::POSITION_RIGHT_LEG;
    }

    protected function getFirstUplineInBinaryTree($user_id)
    {
        $sql = "
            WITH RECURSIVE upline (user_id, parent_id, `level`, binary_user_id) AS (
                SELECT
                    u.id AS user_id,
                    u.sponsorid AS parent_id,
                    1 AS `level`,
                    IF(b.user_id IS NOT NULL, b.user_id, NULL) binary_user_id
                FROM users u
                LEFT JOIN cm_genealogy_binary b ON b.user_id = u.id
                WHERE u.id = $user_id AND u.levelid = 3
                
                UNION ALL
                
                SELECT
                    u.id AS user_id,
                    u.sponsorid AS parent_id,
                    upline.`level` + 1 `level`,
                    IF(b.user_id IS NOT NULL, b.user_id, NULL)
                FROM users u
                JOIN upline ON upline.parent_id = u.id
                LEFT JOIN cm_genealogy_binary b ON b.user_id = u.id
                WHERE u.levelid = 3 AND upline.binary_user_id IS NULL
            )
            SELECT
                up.binary_user_id
            FROM upline AS up
            WHERE up.user_id <> $user_id AND up.binary_user_id IS NOT NULL
            ORDER BY up.level DESC
            LIMIT 1;
        ";

        $smt = $this->db->prepare($sql);
        $smt->bindParam(':user_id', $user_id);
        $smt->execute();
        return $smt->fetchColumn();
    }

    protected function getDeleteUsers()
    {
        $sql = "
            SELECT
                m.user_id, m.parent_id, m.created_at placed_at
            FROM cm_genealogy_binary m
            LEFT JOIN users u ON u.id = m.user_id
            WHERE u.id IS NULL
            ORDER BY m.created_at ASC;
        ";
        $smt = $this->db->prepare($sql);
        $smt->execute();
        return $smt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getDownline($user_id)
    {
        $sql = "
            WITH RECURSIVE downline (user_id, parent_id, `level`, created_at) AS (
                SELECT 
                    gm.user_id,
                    gm.parent_id,
                    0 AS `level`,
                    gm.created_at
                FROM cm_genealogy_binary AS gm
                WHERE gm.user_id = $user_id
                
                UNION ALL
                
                SELECT
                    gm.user_id,
                    gm.parent_id,
                    d.`level` + 1 `level`,
                    gm.created_at
                FROM cm_genealogy_binary AS gm
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

}