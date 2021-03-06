<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\CommissionPeriod;
use App\OfficeGiftCard;

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
VALUES(9,'Rank Consistency','Rank Consistency','monthly','cash','manual',1)");
})->describe('nxm testing');

Artisan::command('olz:rerun', function () {
    $begin = new \DateTime('2021-07-01');
    $end = new \DateTime('2021-07-13');

    $interval = new \DateInterval("P1D");
    $period = new \DatePeriod($begin, $interval, $end);

    foreach ($period as $dt) {
        $date = $dt->format("Y-m-d");
        $this->call("commission:run-volumes-and-ranks", ['date' => $date]);
    }

})->describe('rerun');

Artisan::command('php:info', function () {
    echo phpinfo();
})->describe('PHP Info');


Artisan::command('olz:migrate-user', function () {
    print 'migrating users';
    DB::statement(
        "INSERT INTO users (sponsorid
        ,migrated_userid
        ,migrated_sponsorid
        ,fname
        ,lname
        ,site
        ,created
        ,enrolled_date
        ,active
        ,email
        ,cellphone
        ,dayphone
        ,address
        ,address2
        ,city
        ,state
        ,zip
        ,levelid
        ,referurl) 
        
          SELECT 3
            , id
            ,sponsor_id
            ,first_name
            ,last_name
            ,site_name
            ,enrollment_date
            ,enrollment_date
            , IF(active_status = 'In-service', 'Yes', 'No') as active
            ,email
            ,mobile_number
            ,phone_number
            ,address
            ,address2
            ,city
            ,state
            ,zip
            , 3
            , 'migrated'
        FROM migrate_users"

    );


    print 'updating migrated_users newid' . PHP_EOL;


    /* Update sponsorid
     * UPDATE users SET sponsorid
     * */
    DB::statement(
        "UPDATE migrate_users AS m 
            JOIN users AS u ON u.migrated_userid = m.id  
            SET m.new_id = u.id 
            WHERE u.migrated_userid is not NULL
            AND u.levelid = 3 
        "

    );


    print 'updating sponsorid'. PHP_EOL;

    /* Update sponsorid
     * UPDATE users SET sponsorid
     * */
    DB::statement(
        "UPDATE users u JOIN migrate_users m 
            ON u.migrated_sponsorid = m.id 
             SET u.sponsorid = m.new_id
             WHERE levelid = 3 AND migrated_userid IS NOT NULL
             ;
        "

    );

    print 'category map' . PHP_EOL ;
    /* insert migrated users to categorymap
     * */
    DB::statement(
        "
            INSERT INTO categorymap (userid, catid)
            SELECT u.id, if(m.member_type = 'Customer', 15, 13) as cid FROM migrate_users m 
            join users u on u.migrated_userid = m.id  
            WHERE u.id not in (SELECT userid FROM categorymap)
            AND u.levelid = 3 
        "

    );

    print ' cm affiliates insertion ' . PHP_EOL;
    /* insert migrated users to cm_affiliates
     * */
    DB::statement(
        "
            INSERT INTO cm_affiliates (user_id, cat_id, initial_cat_id, affiliated_at)
            SELECT u.id, 13, 13, cast(m.enrollment_date as date ) FROM users u 
            
            join categorymap c on u.id = c.userid 
            JOIN migrate_users m on u.migrated_userid = m.id 
            where c.catid = 13 and u.referurl = 'migrated'
            AND u.id not in (SELECT user_id FROM cm_affiliates) 
            AND u.levelid = 3
        "

    );


})->describe('Migrate olz users');

Artisan::command('olz:migrate-billing', function(){
    DB::statement(
        "
            INSERT INTO billing (
                userid
                ,billfname
                ,billlname
                ,billaddress
                ,billaddress2
                ,billcity
                ,billstate
                ,billzip
                ,billdate
                ,annualfeemonth
            )
            
            SELECT id
            ,fname
            ,lname            
            ,address
            ,address2
            ,city
            ,state
            ,zip
            ,enrolled_date
            , MONTH(CAST(enrolled_date AS DATE))
            FROM users WHERE migrated_userid IS NOT NULL
            
        ");

})->describe('Migrate billing info');


Artisan::command('olz:migrate-hostess', function(){
        DB::statement("
            insert into users (email, fname, lname, migrated_userid, migrated_sponsorid, referurl, levelid, sponsorid, active)
            select email, first_name, last_name, id, rep_id, 'migrated hostess', 3, 3, 'Yes' FROM migrate_hostess
        ");

        DB::statement("
            INSERT INTO categorymap(userid, catid)
            SELECT id, 80362 FROM users WHERE referurl = 'migrated hostess'
        ");

        /* Update sponsorid
    * UPDATE users SET sponsorid
    * */
        DB::statement(
            "UPDATE users u JOIN migrate_users m 
                ON u.migrated_sponsorid = m.id 
                 SET u.sponsorid = m.new_id
                 WHERE levelid = 3 AND migrated_userid IS NOT NULL
                 ;
            "

        );

})->describe(
    'Party with Hostess Names'
);


Artisan::command('olz:migrate-party', function(){
    DB::statement("
      INSERT INTO cm_hostess_program (user_id, sitename, sponsor_id
        , unique_code, social_link, social_link_shorten
        , start_date, end_date, is_deleted, migrated_party_id) 
        SELECT u.id, u.`site`, u.`sponsorid`
        , u.`site`, 'https://www.opulenzadesigns.com/', 'https://www.opulenzadesigns.com/'
        , p.sd, p.ed, 0, p.id 
        FROM migrate_party p JOIN users u 
          ON 
            u.email = p.email 
        WHERE u.referurl = 'migrated hostess'
    ");
})->describe('migrate party');


//Artisan::command('olz:')


Artisan::command('olz:migrate-ranks-20210820', function () {
    /*
	DB::statement("TRUNCATE cm_daily_volumes");
	DB::statement("TRUNCATE cm_daily_ranks");
	DB::statement("TRUNCATE cm_achieved_ranks");
*/
	//create cm_daily_volumes records here
	$query = DB::table("migrated_rank_history_20210820 AS m")
			->groupBy('m.rankdate')
			->selectRaw("
				m.rankdate
			");

	$query->chunkById(10000, function ($dates) use (&$counter) {
		$counter++;

		foreach ($dates as $date) {
			DB::statement('INSERT INTO cm_daily_volumes (
							user_id, 
							volume_date,
							`level`,
							prs,
							grs,
							sponsored_qualified_representatives_count,
							sponsored_qualified_representatives_users,
							sponsored_leader_or_higher_count,
							sponsored_leader_or_higher_users,
							team_leader_count,
							team_leader_users,
							sr_team_leader_count,
							sr_team_leader_users,
							manager_count,
							manager_users
						)
						WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
							SELECT 
								id AS user_id,
								sponsorid AS parent_id,
								0 AS `level`
							FROM users
							WHERE id = 3 AND levelid = 3
							
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
							LAST_DAY("'.$date->rankdate.'") volume_date, 
							d.level,
							0 prs,
							0 grs,
							0 sponsored_qualified_representatives_count,
							NULL sponsored_qualified_representatives_users,
							0 sponsored_leader_or_higher_count,
							NULL sponsored_leader_or_higher_users,
							0 team_leader_count,
							NULL team_leader_users,
							0 sr_team_leader_count,
							NULL sr_team_leader_users,
							0 manager_count,
							NULL manager_users
						FROM downline d
						WHERE EXISTS(SELECT 1 FROM categorymap c WHERE c.userid = d.user_id AND FIND_IN_SET(c.catid, "13"))            
						ON DUPLICATE KEY UPDATE
							prs = 0,
							grs = 0,
							sponsored_qualified_representatives_count = 0,
							sponsored_qualified_representatives_users = NULL,
							sponsored_leader_or_higher_count = 0,
							sponsored_leader_or_higher_users = NULL,
							team_leader_count = 0,
							team_leader_users = NULL,
							sr_team_leader_count = 0,
							sr_team_leader_users = NULL,
							manager_count = 0,
							manager_users = NULL,
							level = d.level,
							updated_at = CURRENT_TIMESTAMP()');
			}

	}, "rankdate");


	$query = DB::table("migrated_rank_history_20210820 AS m")
			->join('cm_ranks AS r', 'r.name', '=', 'm.rank')
			->join('users AS u', 'u.migrated_userid', '=', 'm.userid')
			->selectRaw("
				u.id AS user_id,
				r.id AS rank_id,
				m.rankdate,
				IF(u.active = 'Yes', 1, 0) AS is_active
			")
        ->orderBy('m.userid')
    ;

	$query->chunk(10000, function ($users) use (&$counter) {
		$counter++;

		foreach ($users as $user) {
			//cm_daily_ranks
			DB::statement('INSERT INTO cm_daily_ranks (user_id, 
											volume_id, 
											rank_date, 
											rank_id, 
											min_rank_id, 
											paid_as_rank_id, 
											is_active,
											is_system_active) 
							SELECT 
								user_id, 
								id AS volume_id, 
								volume_date AS rank_date, 
								'.$user->rank_id.' AS rank_id, 
								'.$user->rank_id.' AS min_rank_id, 
								'.$user->rank_id.' AS paid_as_rank, 
								'.$user->is_active.' AS is_active,
								0 AS is_system_active
							FROM cm_daily_volumes
							WHERE volume_date = LAST_DAY("'.$user->rankdate.'")
							AND user_id = '.$user->user_id.'
							ON DUPLICATE KEY UPDATE 
								min_rank_id         = '.$user->rank_id.',
								rank_id             = '.$user->rank_id.',
                                paid_as_rank_id     = '.$user->rank_id.',
								is_active = 0,
								is_system_active = 0,
								volume_id = VALUES(volume_id),
								updated_at = CURRENT_TIMESTAMP()');

			DB::statement('INSERT INTO cm_achieved_ranks (user_id, rank_id, date_achieved) 
            SELECT 
				'.$user->user_id.',
                r.id,
                LAST_DAY("'.$user->rankdate.'")
            FROM cm_ranks r
            WHERE r.id <=  '.$user->rank_id.'
            ON DUPLICATE KEY UPDATE
                date_achieved = IF(date_achieved < VALUES(date_achieved), date_achieved, VALUES(date_achieved))');
		}

	});

	print 'DONE!' .PHP_EOL;

})->describe('rerun');


Artisan::command('olz:migrate-weekly-payouts-20210820', function () {

	DB::statement(
        "
		INSERT INTO cm_commission_payouts (commission_period_id, user_id, sponsor_id, payee_id, level, commission_value, percent, amount, remarks)
		SELECT
			(SELECT cp.id FROM cm_commission_periods cp WHERE cp.commission_type_id = 1 AND cp.is_locked = 1 AND m.enddate BETWEEN cp.start_date AND cp.end_date LIMIT 1) AS commission_period_id,
			u.id,
			u.id,
			u.id,
			0,
			m.totalpayout,
			100,
			m.totalpayout,
			'Migrated'
		FROM migrated_weekly_payouts_20210820 m
		JOIN users u ON u.migrated_userid = m.userid
		"
    );

	print 'DONE!' .PHP_EOL;

})->describe('rerun');




Artisan::command('olz:generate-commission-weekly-periods', function () {

	//This will generate weekly periods for the migration records
	$first_day_of_the_week = config("commission.first_day_of_the_week", "thursday");

	$today = date('Y-m-d');
	$start_date = date('Y-m-d', strtotime( "$first_day_of_the_week", strtotime(date('2019-01-01')) ) );

	while ($start_date < $today) {
		$end_date = Carbon::createFromFormat("Y-m-d", $start_date)->adddays(6)->format("Y-m-d");

		if (strtotime(date("Y-m-d")) >= strtotime($start_date)) {
			$commission_period = new CommissionPeriod();
			$commission_period->commission_type_id = 1;
			$commission_period->start_date = $start_date;
			$commission_period->end_date = $end_date;
			$commission_period->is_locked = 1;
			$commission_period->save();

			$start_date = date("Y-m-d", strtotime("next $first_day_of_the_week", strtotime($commission_period->start_date)));
		}
	}

	print 'DONE!' .PHP_EOL;

})->describe('rerun');

Artisan::command('olz:migrate-new-order-header', function(){

    DB::statement("  INSERT INTO transactions (invoice, userid, sponsorid
    , transactiondate
    , shipping_fee
    , sub_total
    , remarks
    , migrated_order_id
    , `type`
    , `status`
    )
    SELECT
        h.order_id,
        (SELECT id FROM users AS u WHERE u.migrated_userid = h.purchaser_id) AS new_purchaserid,
        (SELECT id FROM users AS s WHERE s.migrated_userid = h.sponsor_id) AS new_sponsorid,
        h.od,
        h.shipping_fee,
        h.total_product_price,
        'migrated new order header',
        h.id ,
        'product',
        'Approved'
    FROM migrate_new_order_header h
        having new_purchaserid is not null
    ");

    DB::statement("

    UPDATE transactions t 
        JOIN migrate_new_order_header oh ON t.`migrated_order_id` = oh.id 
        JOIN migrate_hostess h ON oh.party_id = h.`party_id` 
        JOIN users u ON u.`migrated_userid` = h.`id`
        SET t.`sponsorid` = u.id, sponsor_catid = 80362     
     WHERE oh.party_id IS NOT NULL AND
        u.`referurl` = 'migrated hostess'
        AND t.remarks = 'migrated new order header'
    ");

    DB::statement("         
          UPDATE transactions t 
    JOIN users u 
        ON t.sponsorid = u.id 
    JOIN categorymap c 
        ON u.id = c.userid 
SET t.sponsor_catid = c.catid
WHERE migrated_order_id IS NOT NULL
    AND u.`levelid` = 3
    AND t.`sponsor_catid` IS NULL
    
         ;
    ");

})->describe('Migrate orders 2');

Artisan::command('olz:migrate-transaction-products', function(){
    DB::statement("
        delete from transaction_products where migrated_product_id is not null;
    ");

    $query = DB::table('migrate_order_products as m')
        ->selectRaw('        
            IFNULL((SELECT distinct(id) FROM transactions t WHERE t.invoice = m.`order_id` and t.sub_total > 0 limit 1), 0) as tid,
            IFNULL((SELECT distinct(product_id) FROM oc_product p WHERE p.sku = m.`sku` limit 1), 0) as spi,
            m.`quantity`,
            m.`unit_price`,
            m.`total`,
            m.cv,
            m.id'
        )
        ->orderBy('m.id');


    $query->chunk(10000, function ($tps) use (&$counter) {
        $counter++;
        foreach($tps as $tp)
        {
            DB::statement("
            INSERT INTO transaction_products(transaction_id, shoppingcart_product_id, quantity, price, total, computed_cv, migrated_product_id)
            VALUES(
              $tp->tid,
              $tp->spi,
              $tp->quantity,
              $tp->unit_price,
              $tp->total,
              $tp->cv,
              $tp->id
            )");

        }
    });
})->describe('Migrate transaction products ');


Artisan::command('olz:migrate-update-transactions', function(){


    DB::statement("         
          UPDATE transactions t 
    JOIN users u 
        ON t.sponsorid = u.id 
    JOIN categorymap c 
        ON u.id = c.userid 
SET t.sponsor_catid = c.catid
WHERE migrated_order_id IS NOT NULL
    AND u.`levelid` = 3
    AND t.`sponsor_catid` IS NULL
    
         ;
    ");

})->describe('Update sponsor cat id');


Artisan::command('olz:migrate-gift-cards', function(){

    $query = DB::table('migrated_gift_cards_20210820 as m')
        ->join('users as u', 'u.migrated_userid', '=', 'm.userid')
        ->selectRaw('u.id as userid, m.validationcode, u.id, m.balance, m.amount, u.email ')
        ->orderBy('m.userid');


    $query->chunk(10000, function ($gcs) use (&$counter) {
        $counter++;

        foreach ($gcs as $gc) {
            $c = new \App\OfficeGiftCard();
            $c->validationcode = $gc->validationcode;
            $c->userid = $gc->userid;
            $c->balance = $gc->balance;
            $c->amount = $gc->amount;
            $c->email = $gc->email;
            $c->save();
        }

    });

})->describe('Migrate gift cards');

Artisan::command('olz:migrate-rank-correction', function(){
    $query = DB::table("migrated_rank_history_20210820 AS m")
        ->join('cm_ranks AS r', 'r.name', '=', 'm.rank')
        ->join('users AS u', 'u.migrated_userid', '=', 'm.userid')
        ->selectRaw("
				u.id AS user_id,
				r.id AS rank_id,
				m.rankdate,
				IF(u.active = 'Yes', 1, 0) AS is_active
			")
        ->orderBy('m.userid')
    ;
    $query->chunk(10000, function ($users) use (&$counter) {
        $counter++;

        foreach ($users as $user) {
            //cm_daily_ranks
            DB::statement('INSERT INTO cm_daily_ranks (user_id, 
											volume_id, 
											rank_date, 
											rank_id, 
											min_rank_id, 
											paid_as_rank_id, 
											is_active,
											is_system_active) 
							SELECT 
								user_id, 
								id AS volume_id, 
								volume_date AS rank_date, 
								'.$user->rank_id.' AS rank_id, 
								'.$user->rank_id.' AS min_rank_id, 
								'.$user->rank_id.' AS paid_as_rank, 
								'.$user->is_active.' AS is_active,
								0 AS is_system_active
							FROM cm_daily_volumes
							WHERE volume_date = LAST_DAY("'.$user->rankdate.'")
							AND user_id = '.$user->user_id.'
							ON DUPLICATE KEY UPDATE 
								min_rank_id         = '.$user->rank_id.',
								rank_id             = '.$user->rank_id.',
                                paid_as_rank_id     = '.$user->rank_id.',
								is_active = 0,
								is_system_active = 0,
								volume_id = VALUES(volume_id),
								updated_at = CURRENT_TIMESTAMP()');

            DB::statement('INSERT INTO cm_achieved_ranks (user_id, rank_id, date_achieved) 
            SELECT 
				'.$user->user_id.',
                r.id,
                LAST_DAY("'.$user->rankdate.'")
            FROM cm_ranks r
            WHERE r.id <=  '.$user->rank_id.'
            ON DUPLICATE KEY UPDATE
                date_achieved = IF(date_achieved < VALUES(date_achieved), date_achieved, VALUES(date_achieved))');
        }

    });

})->describe(' Migrate rank rerun with updated on duplicate');


Artisan::command('olz:migrate-default-user-password', function (){
    DB::statement(

        "UPDATE users SET password = '0/dlUkLvnPmPcdc=' WHERE migrated_userid is not null"
    );

})->describe('SET default password for migrated users');


Artisan::command('olz:update-commission-weekly-periods', function () {

	DB::statement("         
		UPDATE cm_commission_periods
		SET 
		start_date = DATE_SUB(start_date, INTERVAL 4 DAY),
		end_date = DATE_SUB(end_date, INTERVAL 4 DAY)
		WHERE commission_type_id = 1;
    ");

})->describe('rerun');