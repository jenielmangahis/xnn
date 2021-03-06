<?php

use Illuminate\Support\Facades\DB;
use Commissions\Admin\RunCommission;
use Carbon\Carbon;
use App\User;
use Commissions\Clients\Payeer;
/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('nxm:test', function () {
 DB::statement("INSERT INTO cm_commission_types(id,name,description,frequency,payout_type,run_type,is_active)
                VALUES(4,'Rank Incentives','Rank Incentives','monthly','cash','manual',1);
");
})->describe('nxm testing');

Artisan::command('php:info', function () {
    echo phpinfo();
})->describe('PHP Info');

Artisan::command('mba:rerun', function () {
    $run_commission = new RunCommission();
    $run_commission->run('1711');
})->describe('MBA rerun');


Artisan::command("mba:migrate-users", function () {
    $this->info('Time started - ' . Carbon::now());
    $counter = 0;
    $p_user = new User();
    $p_user->updateExistingRecord();

    DB::table("mba_oc_customer AS c")
        ->leftJoin('mba_ft_individual AS i', 'i.oc_customer_ref_id', '=', 'c.customer_id')
        ->whereRaw("NOT EXISTS(SELECT 1 FROM users u WHERE u.migrated_user_id = i.id)")
        ->orderBy("c.customer_id")
        ->selectRaw("
            c.customer_id,
            0 AS sponsorid,
            IFNULL(i.`user_name`, CONCAT(c.`firstname`, i.id)) AS site,
            'password123' AS password,
            'Yes' AS active,
            IFNULL(c.`firstname`, '') AS fname,
            IFNULL(c.`lastname`, '') AS lname,
            '' AS address,
            '' AS city,
            '' AS state,
            '' AS zip,
            '' AS country,
            c.`email` AS email,
            c.`telephone` AS dayphone,
            c.`telephone` AS evephone,
            3 AS levelid,
            c.`telephone` AS cellphone,
            i.id AS memberid,
            IFNULL(i.`sponsor_id`, 0) AS sponmemberid,
            'migrated' AS referurl,
            i.`date_of_joining` AS enrolled_date,
            NOW() AS created
        ")
        ->chunkById(10000, function ($users) use (&$counter) {
            $counter++;
            foreach ($users as $user) {
                //$this->info(print_r($user, true));
                $u = new User();
                $u->sponsorid = $user->sponsorid; // TODO: temporary sponsor. need to update the correct sponsor after all the users are inserted
                $u->site = $user->site;
                $u->password = $user->password;
                $u->active = $user->active;
                $u->fname = $user->fname;
                $u->lname = $user->lname;
                $u->address = $user->address;
                $u->city = $user->city;
                $u->state = $user->state;
                $u->zip = $user->zip;
                $u->country = $user->country;
                $u->email = $user->email;
                $u->dayphone = $user->dayphone;
                $u->evephone = $user->evephone;
                $u->levelid = $user->levelid;
                $u->cellphone = $user->cellphone;
                $u->memberid = $user->memberid;
                $u->migrated_user_id = $user->memberid;
                $u->sponmemberid = $user->sponmemberid;
                $u->migrated_sponsor_id = $user->sponmemberid;
                $u->referurl = $user->referurl;
                $u->enrolled_date = $user->enrolled_date;
                $u->created = $user->created;
                $u->save();
            }
            $this->info("DONE BATCH $counter " . Carbon::now());
            //return false;
        }, "customer_id");

        // updated user's sponsor ID
        DB::statement("
            UPDATE users u
            LEFT JOIN users s ON s.migrated_user_id = u.migrated_sponsor_id
                SET u.sponsorid = s.id
            WHERE u.migrated_user_id IS NOT NULL AND IFNULL(u.sponsorid, 0) = 0;
        ");

        // set the root ID 3
        DB::statement("
            UPDATE users u
                SET u.sponsorid = 3
            WHERE EXISTS(SELECT 1 FROM mba_ft_individual WHERE user_level = 0 AND sponsor_level = 0 AND id = u.`migrated_user_id`);
        ");

    $this->info('Time ended - ' . Carbon::now());
});

Artisan::command("mba:migrate-category-ids", function () {
    $this->info('Time started - ' . Carbon::now());

    // Affiliates
    DB::statement("
        INSERT INTO categorymap (userid, catid)
        SELECT 
            u.id,
            14
        FROM users u
        LEFT JOIN categorymap cm ON cm.userid = u.id
        JOIN mba_ft_individual m ON m.id = u.`migrated_user_id`
        WHERE u.migrated_user_id IS NOT NULL AND cm.catid IS NULL AND u.`referurl` = 'migrated' AND m.`user_rank_id` IS NOT NULL;
    ");

    // Customers
    DB::statement("
        INSERT INTO categorymap (userid, catid)
        SELECT 
            u.id,
            13
        FROM users u
        LEFT JOIN categorymap cm ON cm.userid = u.id
        JOIN mba_ft_individual m ON m.id = u.`migrated_user_id`
        WHERE u.migrated_user_id IS NOT NULL AND cm.catid IS NULL AND u.`referurl` = 'migrated' and m.`user_rank_id` IS NULL;
    ");

    $created_date = '2021-06-30';

    DB::statement("
        UPDATE cm_affiliates a 
            SET affiliated_at = '$created_date', created_at = '$created_date', updated_at = '$created_date'
        WHERE EXISTS (SELECT 1 FROM users WHERE referurl = 'migrated' AND migrated_user_id IS NOT NULL AND id = a.`user_id`);
    ");

    DB::statement("
        UPDATE cm_customers a 
            SET enrolled_at = '$created_date', enrolled_date = '$created_date'
        WHERE EXISTS (SELECT 1 FROM users WHERE referurl = 'migrated' AND migrated_user_id IS NOT NULL AND id = a.`user_id`);
    ");

    $this->info('Time ended - ' . Carbon::now());
});

Artisan::command("mba:migrate-minimum-ranks", function () {
    $this->info('Time started - ' . Carbon::now());
    
    $start_date = '2021-06-01';
    $end_date = '2021-06-30';

    DB::statement("
        INSERT INTO cm_minimum_ranks (user_id, rank_id, start_date, end_date)
        SELECT 
            u.id, 
            r.id,
            '$start_date' AS start_date,
            '$end_date' AS end_date
        FROM mba_ft_individual a
        JOIN users u ON u.migrated_user_id = a.id
        JOIN cm_ranks r ON r.id = a.`user_rank_id` + 2
        WHERE a.user_rank_id IS NOT NULL;
    ");

    $this->info('Time ended - ' . Carbon::now());
});

Artisan::command("mba:encrypt-password", function () {
    $this->info('Time started - ' . Carbon::now());
    $counter = 0;
    require '/nxm/rep/code/office/php/sys/Encryption.php';

    $p_user = new User();

    DB::table("users AS u")
        ->where('referurl', 'migrated')
        ->where('password', 'password123')
        ->orderBy("u.id")
        ->selectRaw("
            u.id,
            u.password
        ")
        ->chunkById(10000, function ($users) use (&$counter, $p_user) {
            $counter++;
            foreach ($users as $user) {
                $user->password = $p_user->generateRandomPassword(10);
                //  $password = $user->password !== "" ? \Encryption::encryptText($user->password, "encrypt") : null;

                $password = \Encryption::encryptText($user->password, "encrypt");
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['password' => $password]);

            }
            $this->info("DONE BATCH $counter " . Carbon::now());
            //return false;
        }, "id");

    $this->info('Time ended - ' . Carbon::now());
});

Artisan::command("mba:initialize-volumes-and-ranks", function () {
    $this->info('Time started - ' . Carbon::now());

    $affiliates = config('commission.member-types.affiliates');
    $root_id = 3;
    $end_date = '2021-06-30';

    // initialize volumes
    DB::statement("
        INSERT INTO cm_daily_volumes (
            user_id, 
            volume_date, 
            pv,
            gv, 
            pv_current_date,
            group_volume_left_leg,
            group_volume_right_leg,
            active_personal_enrollment_count,
            active_personal_enrollment_users,
            level
        )
        WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
            SELECT 
                id AS user_id,
                sponsorid AS parent_id,
                0 AS `level`
            FROM users
            WHERE id = $root_id AND levelid = 3
            
            UNION ALL
            
            SELECT
                p.id AS user_id,
                p.sponsorid AS parent_id,
                downline.`level` + 1 `level`
            FROM users p
            INNER JOIN downline ON p.sponsorid = downline.user_id
            WHERE p.levelid = 3
        )
        SELECT
            d.user_id, 
            '$end_date' volume_date, 
            0 pv,
            0 gv,
            0 pv_current_date,
            0 group_volume_left_leg,
            0 group_volume_right_leg,
            0 active_personal_enrollment_count,
            NULL active_personal_enrollment_users,
            d.level
        FROM downline d
        JOIN cm_affiliates a ON a.user_id = d.user_id
        WHERE EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = d.user_id AND FIND_IN_SET(c.catid, '$affiliates'))
            AND EXISTS(SELECT 1 FROM users WHERE referurl = 'migrated' AND id = a.user_id)
            AND a.affiliated_date <= '$end_date'
        ON DUPLICATE KEY UPDATE
            pv = 0,
            gv = 0,
            pv_current_date = 0,
            group_volume_left_leg = 0,
            group_volume_right_leg = 0,
            active_personal_enrollment_count = 0,
            active_personal_enrollment_users = NULL,
            level = d.level,
            updated_at = CURRENT_TIMESTAMP()
    ");

    // initialize ranks
    DB::statement("
        INSERT INTO cm_daily_ranks (
            user_id, 
            volume_id, 
            rank_date, 
            rank_id, 
            min_rank_id, 
            paid_as_rank_id,
            is_active,
            is_system_active,
            is_qualified_trader_or_higher,
            rank_last_90_days
        )
        SELECT 
            dv.user_id, 
            dv.id AS volume_id, 
            dv.volume_date AS rank_date, 
            1 AS rank_id, 
            1 AS min_rank_id, 
            1 AS paid_as_rank, 
            0 AS is_active,
            0 AS is_system_active,
            0 AS is_qualified_trader_or_higher,
            1 AS rank_last_90_days
        FROM cm_daily_volumes dv
        WHERE volume_date = '$end_date'
        ON DUPLICATE KEY UPDATE 
            min_rank_id = 1,
            rank_id = 1,
            paid_as_rank_id = 1,
            is_active = 0,
            is_system_active = 0,
            is_qualified_trader_or_higher = 0,
            rank_last_90_days = 1,
            volume_id = VALUES(volume_id),
            updated_at = CURRENT_TIMESTAMP();
    ");

    // set minimum ranks
    DB::statement("
        UPDATE cm_daily_ranks dr
        JOIN cm_minimum_ranks mr ON mr.user_id = dr.user_id
            SET dr.min_rank_id = mr.rank_id, dr.paid_as_rank_id = mr.rank_id, dr.rank_id = mr.rank_id
        WHERE mr.is_deleted = 0 AND dr.rank_date = '$end_date' AND '$end_date' BETWEEN mr.start_date AND mr.end_date;
    ");

    $this->info('Time ended - ' . Carbon::now());
});

Artisan::command("mba:migrate-volumes", function () {
    $this->info('Time started - ' . Carbon::now());
    
    $end_date = '2021-06-30';

    DB::statement("
        UPDATE cm_daily_volumes dv
        JOIN users u ON u.`id` = dv.`user_id`
        JOIN mba_ft_individual m ON m.id = u.`migrated_user_id`
            SET dv.pv = m.`personal_pv`, dv.`gv` = m.`gpv`, dv.`total_group_volume_left_leg` = m.`total_left_carry`, dv.`total_group_volume_right_leg` = m.`total_right_carry`
        WHERE dv.`volume_date` = '$end_date';
    ");

    // set greater and lesser volumes
    DB::statement("
        UPDATE cm_daily_volumes dv
        LEFT JOIN (
            SELECT
                sdv.user_id,
                CASE
                    WHEN sdv.total_group_volume_left_leg > sdv.total_group_volume_right_leg THEN sdv.total_group_volume_left_leg
                    ELSE sdv.total_group_volume_right_leg
                END greater_volume,
                CASE
                    WHEN sdv.total_group_volume_left_leg < sdv.total_group_volume_right_leg THEN sdv.total_group_volume_left_leg
                    ELSE sdv.total_group_volume_right_leg
                END lesser_volume
            FROM cm_daily_volumes sdv
            WHERE sdv.volume_date = '$end_date'
        ) AS a ON a.user_id = dv.user_id 
        SET
            dv.greater_volume = COALESCE(a.greater_volume, 0),
            dv.lesser_volume = COALESCE(a.lesser_volume, 0)
        WHERE dv.volume_date = '$end_date';
    ");

    $this->info('Time ended - ' . Carbon::now());
});


Artisan::command("mba:migrate-binary", function () {
    $this->info('Time started - ' . Carbon::now());

    $migrated_root_id = 781;

    DB::statement("
        DELETE FROM cm_genealogy_binary WHERE 1 = 1;
    ");

    DB::statement("
        INSERT INTO cm_genealogy_binary(user_id, parent_id, `position`, placement_preference)
        VALUES
        (3, 0, 0, 'LESSER_VOLUME_LEG'),
        (20, 0, 0, 'LESSER_VOLUME_LEG'),
        ($migrated_root_id, 3, 0, 'LESSER_VOLUME_LEG')
    ");

    DB::statement("
        INSERT INTO cm_genealogy_binary(user_id, parent_id, `position`, placement_preference)
        SELECT 
            u.id, IFNULL(up.id, 0) parent_id, IF(a.position = 'R', 1, 0), 'LESSER_VOLUME_LEG'
        FROM mba_ft_individual a
        JOIN users u ON u.`migrated_user_id` = a.`id`
        LEFT JOIN users up ON up.`migrated_user_id` = a.father_id AND IFNULL(up.`migrated_user_id`, 0) <> 0
        WHERE a.`position` IS NOT NULL AND u.`migrated_user_id` IS NOT NULL
        ON DUPLICATE KEY UPDATE
            parent_id = VALUES(parent_id),
            `position` = VALUES(`position`)
        ;
    ");

    $this->info('Time ended - ' . Carbon::now());
});


Artisan::command("mba:migrate-updates", function () {
    $this->info('Time started - ' . Carbon::now());

    // $created_date = '2021-06-30';

    DB::statement("
        UPDATE cm_ranks SET name = 'IBO', description = 'IBO' WHERE id IN (1, 2);
    ");

    $this->info('Time ended - ' . Carbon::now());
});

Artisan::command("mba:get-payeer-history", function () {

	$payeer = new Payeer('P1054097774', '1442678883', '123456789', 'https://payeer.com/ajax/api/api.php');

	echo $payeer->getHistory();
});