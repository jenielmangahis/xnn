<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\User;

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

Artisan::command('515:test', function () {
    DB::statement("INSERT INTO cm_commission_group_types(group_id,type_id) VALUES(2,1)");
    DB::statement("INSERT INTO cm_commission_group_types(group_id,type_id) VALUES(3,3)");
    DB::statement("INSERT INTO cm_commission_group_types(group_id,type_id) VALUES(3,5)");

    $this->info("done");
})->describe('515 testing');

Artisan::command('php:info', function () {
    echo phpinfo();
})->describe('PHP Info');


function v____generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

Artisan::command("515:migrate-associates", function () {
    $this->info('Time started - ' . Carbon::now());
    $counter = 0;
    require '/nxm/rep/code/office/php/sys/Encryption.php';

    $sourceTable = "migrated_associates_as_of_sept13";

    DB::statement("
        INSERT INTO users (
            id,
            migrated_user_id,
            sponsorid,
            fname,
            mname,
            lname,
            site,
            enrolled_date,
            active,
            email,
            cellphone,
            dayphone,
            evephone,
            levelid,
            referurl,
            address,
            city,
            state,
            country,
            zip,
            cf,
            piva,
            codice_sdi,
            iban,
            date_of_birth,
            created,
            memberid
        )
        SELECT
            m.AssociateID AS id,
            m.AssociateID AS migrated_user_id,
            m.SponsorID AS sponsorid,
            m.FirstName AS fname,
            m.MiddleName AS mname,
            m.LastName AS lname,
            m.AssociateID AS site,
            m.DateEnrolled AS enrolled_date,
            m.Active AS active,
            m.Email AS email,
            m.MobileNumber AS cellphone,
            m.PhoneNumber AS dayphone,
            m.PhoneNumber AS evephone,
            3 AS levelid,
            'migrated' AS referurl,
            
            m.Address AS address,
            m.City AS city,
            m.State AS state,
            m.Country AS country,
            m.Zip AS zip,
            
            m.CF AS cf,
            m.`P IVA` AS piva,
            m.`CODICE SDI` AS codice_sdi,
            m.IBAN AS iban,
            m.birth_date AS date_of_birth,
            m.DateEnrolled AS created,
            m.PlankAssociateID AS memberid
        FROM $sourceTable m
        ON DUPLICATE KEY UPDATE
            migrated_user_id = VALUES(migrated_user_id),
            sponsorid = VALUES(sponsorid),
            fname = VALUES(fname),
            mname = VALUES(mname),
            lname = VALUES(lname),
            site = VALUES(site),
            enrolled_date = VALUES(enrolled_date),
            active = VALUES(active),
            email = VALUES(email),
            cellphone = VALUES(cellphone),
            dayphone = VALUES(dayphone),
            evephone = VALUES(evephone),
            levelid = VALUES(levelid),
            referurl = VALUES(referurl),
            address = VALUES(address),
            city = VALUES(city),
            state = VALUES(state),
            country = VALUES(country),
            zip = VALUES(zip),
            cf = VALUES(cf),
            piva = VALUES(piva),
            codice_sdi = VALUES(codice_sdi),
            iban = VALUES(iban),
            date_of_birth = VALUES(date_of_birth),
            memberid = VALUES(memberid)
        ;
    ");

    DB::statement("
        INSERT INTO categorymap (userid, catid)
        SELECT
            m.AssociateID,
            14
        FROM $sourceTable m
        LEFT JOIN categorymap cm ON cm.userid = m.AssociateID
        WHERE cm.catid IS NULL;
    ");

    DB::statement("
        UPDATE $sourceTable m
        JOIN cm_affiliates a ON a.user_id = m.AssociateID
        SET a.affiliated_at = m.DateEnrolled
        WHERE a.user_id = m.AssociateID;
    ");

    $query = DB::table("$sourceTable AS mu")
        ->orderBy("mu.AssociateID")
        ->selectRaw("
            mu.AssociateID
        ");

    $query->chunkById(10000, function ($users) use (&$counter) {
        $counter++;

        foreach ($users as $user) {

            $u = \App\User::find($user->AssociateID);
            $u->password = \Encryption::encryptText('HywDb4L3!', "encrypt"); // Set default pass to HywDb4L3!

            $u->save();
        }
        $this->info("DONE BATCH $counter " . Carbon::now());

    }, "AssociateID");

    $this->info('Time ended - ' . Carbon::now());
});

Artisan::command('515:change-password-migrated-associates', function () {

   //DB::statement("UPDATE users u SET u.password = '6+9hZVe0oq6f' WHERE u.migrated_user_id IS NOT NULL AND u.password_changed = 0 AND u.password != '6+9hZVe0oq6f' LIMIT 100;");
   // DB::statement("UPDATE users u SET u.password = '6+9hZVe0oq6f' WHERE u.migrated_user_id IS NOT NULL AND u.password != '6+9hZVe0oq6f' AND u.id IN (18151,25392,26036,25278,18032,15669,26002,23573,23376,17847) LIMIT 100;");
    // task: https://3.basecamp.com/3526928/buckets/19336458/todos/3897351461#__recording_3897368836
    DB::statement("UPDATE users u SET u.password = '6+9hZVe0oq6f' WHERE u.migrated_user_id IS NOT NULL AND u.password != '6+9hZVe0oq6f' AND u.password_changed = 0");

    $this->info("Done!");

})->describe('Client wants to set a single password for migrated users as part of their demo.');

Artisan::command("515:migrate-energy-accounts", function () {
    $this->info('Time started - ' . Carbon::now());
    $counter = 0;

    $query = DB::table("migrated_energy_accounts_as_of_june5 AS me")
        ->orderBy("me.plankEnergyAccountId")
        ->selectRaw("
            me.plankEnergyAccountId
        ")->whereRaw("custid IS NOT NULL AND referenceId IS NOT NULL");

//    foreach ($query as $user) {
//        $this->info("Plank Energy Account ID: $user->plankEnergyAccountId");
//    }

    $query->chunkById(10000, function ($users) use (&$counter) {
        $counter++;

        foreach ($users as $user) {
            
            DB::statement("CALL import_energy_account(?)",array($user->plankEnergyAccountId));
        }
        $this->info("DONE BATCH $counter " . Carbon::now());

    }, "plankEnergyAccountId");

    $this->info('Time ended - ' . Carbon::now());
});



Artisan::command('515:payout-fixes-from-client', function () {

// did not receive commission because of migration error 
   DB::statement("INSERT INTO cm_commission_payouts (commission_period_id
	, transaction_id
	, user_id
	, sponsor_id
	, payee_id
	, level
	, commission_value
	, percent
	, amount
	, remarks
	, currency
	, created_at
	, updated_at)

	  SELECT
	    226,
	    cea.id,
	    cea.customer_id,
	    cea.sponsor_id,
	    cea.sponsor_id,
	    0,
	    IF(cea.account_type = 1, 20, 25) cv,
	    100,
	    IF(cea.account_type = 1, 20, 25) amount,
	    'Part 1 :  As a Watt or above title, newly enrolled approved energy account 20 or 25 euro.' remarks,
	    'EUR',
	    NOW(),
	    NOW()
	  FROM cm_energy_accounts cea
	  WHERE cea.reference_id IN
	  ('IT001E99860355',
	  '03340010273861',
	  'IT001E41783139',
	  '03340012008618',
	  'IT001E43267644',
	  'IT001E42888743',
	  '15104203746926',
	  'IT001E45114402',
	  'IT001E98195974',
	  '03340007568268',
	  'IT001E43612702',
	  'IT001E98976901')
	  AND id NOT IN (SELECT
	      ceal.energy_account_id
	    FROM cm_energy_account_logs ceal
	    WHERE ceal.current_status = 7
	    AND ceal.created_date <= '2021-06-06');");

// not paid in the old system so we are paying them in the new system
DB::statement("UPDATE cm_energy_account_logs ceal SET ceal.created_at = '2021-06-11' 
  WHERE ceal.current_status = 4
  AND ceal.energy_account_id IN (29550,36061,38161,38200,38353,38629,39601,39871,40294,40297,40300,41071,41074,41098,41155,41197,41233,37189,41716,41533,41722,42118,42310,42160,42214,41359,42295,42157,41548,36043,41488,42469,42436,42502,41491,41479,42697,42793,42658,42904,42907,42925,42901,37237,40654,41755,43167,43170,41521,41845,33871,39862,43486,40828,41746,35911,43762,42364,44071,44074,44086,44089,44113,44029,41320,42280,41989,41623,41767,43155,42511,44329,44326,44371,34245,34248,44671,45223,45172,45157,45160,45166,45205,45208,45526,45514,45523,38128);");

// moving the cancelled date so the system pays the immediate earnings
DB::statement("UPDATE cm_energy_account_logs ceal SET ceal.created_at = '2021-06-15' 
  WHERE ceal.current_status = 7
  AND ceal.energy_account_id IN (29550,36061,38161,38200,38353,38629,39601,39871,40294,40297,40300,41071,41074,41098,41155,41197,41233,37189,41716,41533,41722,42118,42310,42160,42214,41359,42295,42157,41548,36043,41488,42469,42436,42502,41491,41479,42697,42793,42658,42904,42907,42925,42901,37237,40654,41755,43167,43170,41521,41845,33871,39862,43486,40828,41746,35911,43762,42364,44071,44074,44086,44089,44113,44029,41320,42280,41989,41623,41767,43155,42511,44329,44326,44371,34245,34248,44671,45223,45172,45157,45160,45166,45205,45208,45526,45514,45523,38128);");

// no record of approved date
DB::statement("INSERT INTO cm_energy_account_logs(created_at, updated_at, notes, energy_account_id, current_status, customer_id, reference_id)
  VALUES ('2021-06-11', NOW(), 'migrated', 43924, 4, 466819, '01611042004235');");
DB::statement("INSERT INTO cm_energy_account_logs(created_at, updated_at, notes, energy_account_id, current_status, customer_id, reference_id)
  VALUES ('2021-06-11', NOW(), 'migrated', 42223, 4, 465832, '01611537000055');");

// adjust 3758 payout from 34 to 32
DB::statement("DELETE FROM cm_commission_payouts WHERE id = 7801228;");

// adjust 4512 payout from 65 to 25
DB::statement("DELETE FROM cm_commission_payouts WHERE id IN (7801126,7801135);");

// incorrectly marked as paid by admin
DB::statement("UPDATE cm_commission_payouts ccp SET ccp.is_paid = 0 WHERE ccp.payee_id IN (14728,16429,23553,26078) AND ccp.commission_period_id = 226;");

   $this->info("Done!");

})->describe('Refer to IMMEDIATE EARNING PAY DATE 7th of JUNE (1).xlsx');


Artisan::command('515:delete-from-paid-table', function () {

	DB::statement("DELETE FROM migrated_energy_accounts_paid WHERE account_num IN ('10400000943878','11821000679980','15104203473499','15365757000883','15441000136443','15442000319489','IT001E00214678','IT001E00499880','IT001E04292303','IT001E04495412','IT001E04641631','IT001E07063563','IT001E07230164','IT001E10169625','IT001E10720306','IT001E14678286','IT001E16512603','IT001E17181817','IT001E17511494','IT001E17566265','IT001E26502493','IT001E26536658','IT001E39278455','IT001E41945750','IT001E42387323','IT001E43114586','IT001E43255433','IT001E43485912','IT001E43637777','IT001E43687304','IT001E46901883','IT001E47293575','IT001E49073222','IT001E49663099','IT001E53915718','IT001E60052425','IT001E60866582','IT001E61948214','IT001E64575344','IT001E67257822','IT001E67289247','IT001E69275299','IT001E71966838','IT001E76082167','IT001E76921835','IT001E78193542','IT001E80809945','IT001E90596476','IT001E91551197','IT001E91686672','IT001E93484777','IT001E97674735','IT001E98335423','IT001E98485756','IT001E98943232','IT001E98980421','IT001E98991584','IT001E98993516','IT001E99468506','IT020E00378515','IT023E00119004',
		'00102400265723',
		'00594201200526',
		'00880000963233',
		'00881205889775',
		'00882603831955',
		'01500620078270',
		'01611042004235',
		'01611042009627',
		'01611062001762',
		'01611537000055',
		'01611901000776',
		'01613243000288',
		'01613477001004',
		'01613890012251',
		'01613890029758',
		'03081000627313',
		'04180000030429',
		'05780000085258') ");



$this->info("Done!");

})->describe('Refer to IMMEDIATE EARNING PAY DATE 7th of JUNE (1).xlsx');

Artisan::command('515:revert-paid-commissions', function () {

    /* DB::statement("UPDATE cm_commission_payouts ccp SET ccp.is_paid = 0 
            WHERE ccp.id IN (SELECT cpd.payout_id FROM cm_payment_details cpd WHERE cpd.payment_id IN (SELECT cp.id FROM cm_payments cp WHERE cp.created_date='2021-06-15'));"); */

    /* DB::statement("DELETE FROM cm_payment_details WHERE payment_id IN (SELECT cp.id FROM cm_payments cp WHERE cp.created_date='2021-06-15')"); */

    /* DB::statement("DELETE FROM cm_payment_history WHERE status = 'PENDING_UPLOAD'"); */

    /* DB::statement("DELETE FROM cm_payments WHERE history_id NOT IN (SELECT cph.id FROM cm_payment_history cph)"); */

$this->info("Done!");

})->describe('Client requested to revert the generated pay files on 2021-06-15');


Artisan::command('515:unlock-comm-periods', function () {

    DB::statement("UPDATE cm_commission_periods ccp SET ccp.is_locked = 0 WHERE ccp.id IN (244,193,199)");

$this->info("Done!");

})->describe('Client requested to unlock these periods: https://3.basecamp.com/3526928/buckets/15863659/todos/3167012669#__recording_3872378069');


Artisan::command('515:update-approved-dates', function () {

    DB::statement("UPDATE cm_energy_account_logs ceal SET ceal.created_at = '2021-06-19' WHERE ceal.current_status = 4
		  AND ceal.energy_account_id IN (29089,40498,45544,42664,42661)");
    DB::statement("UPDATE cm_energy_account_logs ceal SET ceal.created_at = '2021-06-23' WHERE ceal.current_status = 7
		  AND ceal.energy_account_id IN (29089,40498,45544,42664,42661)");

$this->info("Done!");

})->describe('Client requested to move their approved dates so they can pay immediate earnings: https://3.basecamp.com/3526928/buckets/15863659/todos/3167012669#__recording_3872232107');


Artisan::command('515:fix-uploaded-amounts-pay-comm', function () {

    DB::statement("UPDATE cm_payments cp SET 
					taxes_vat = REPLACE(REPLACE(cp.taxes_vat,'?',''),' ',''),
					taxes_trattenuta_previd = REPLACE(REPLACE(cp.taxes_trattenuta_previd,'?',''),' ',''),
					taxes_ritenuta_irpef = REPLACE(REPLACE(cp.taxes_ritenuta_irpef,'?',''),' ',''),
					cp.total_net_amount = ROUND(
					  IF (cp.piva IS NOT NULL AND cp.piva != '',

					      cp.total_gross 
					      + REPLACE(REPLACE(cp.taxes_vat,'?',''),' ','')
					      - REPLACE(REPLACE(cp.taxes_trattenuta_previd,'?',''),' ','')
					      - REPLACE(REPLACE(cp.taxes_ritenuta_irpef,'?',''),' ',''),
					      
					      cp.total_gross 
					      - REPLACE(REPLACE(cp.taxes_ritenuta_irpef,'?',''),' ','')
					  ), 2)
					WHERE cp.created_date='2021-06-10'");

$this->info("Done!");

})->describe('Fixing the values saved in db due to client error: https://3.basecamp.com/3526928/buckets/15863659/todos/3167012669#__recording_3872115514');

Artisan::command('515:update-comm-group-type-mapping', function () {

	DB::statement("UPDATE cm_commission_groups ccg SET ccg.name = 'Weekly Earnings' WHERE ccg.id = 1");
	DB::statement("UPDATE cm_commission_groups ccg SET ccg.name = 'Monthly Earnings' WHERE ccg.id = 2");
	DB::statement("DELETE FROM cm_commission_groups WHERE id = 3");
	DB::statement("UPDATE cm_commission_group_types ccgt SET ccgt.group_id = 1 WHERE ccgt.type_id IN (1,7)");
	DB::statement("UPDATE cm_commission_group_types ccgt SET ccgt.group_id = 2 WHERE ccgt.type_id IN (2,3,4,5)");

$this->info("Done!");

})->describe('Client requested to change the mapping in Detailed Commission dropdowns: https://3.basecamp.com/3526928/buckets/15863659/todos/3346873932#__recording_3880160249');

Artisan::command('515:remove-cancelled-status', function () {

	DB::statement("DELETE FROM cm_energy_account_logs WHERE current_status = 7 AND energy_account_id = 44029;");
	DB::statement("DELETE FROM cm_energy_account_logs WHERE current_status = 5 AND energy_account_id = 44029;");
	DB::statement("UPDATE cm_energy_accounts cea SET cea.status = 4 WHERE cea.id = 44029;");

$this->info("Done!");

})->describe('Client confirms that this is not a cancelled account: IT001E43255433');


Artisan::command('515:remove-duplicate-energy-accounts', function () {
// 2021-06-25
	DB::statement("DELETE
	  FROM cm_energy_accounts
	WHERE id IN (30100, 30118, 30124, 30976, 31003, 31009, 31015, 31258, 40918, 41506, 41512, 41518, 41524, 42619, 43185, 43191, 44548, 44554, 44560, 44566, 44572, 45103, 45349, 45583, 45589, 45625, 46366, 46519, 46648, 46846, 46909, 47362, 47686, 47902, 47908, 47914)");


$this->info("Done!");

})->describe('Duplicate Plank Energy Account IDs');

Artisan::command('515:apply-index-table', function () {
// 2021-06-26

	/*DB::statement("ALTER TABLE cm_energy_accounts
		ADD CONSTRAINT plank_energy_account_id 
		UNIQUE INDEX (plank_energy_account_id)");*/
        DB::statement("ALTER TABLE `cm_energy_account_status_logs` ADD INDEX `idx_plank_energy_account_id` (`plank_energy_account_id`)");


$this->info("Done!");

})->describe('Duplicate Plank Energy Account IDs');

Artisan::command('515:remove-cancelled-status-jun26', function () {
// 2021-06-26
	DB::statement("DELETE FROM cm_energy_account_logs WHERE energy_account_id IN (38398,37087,40330,40606,37696,28958,47233) AND current_status = 7 LIMIT 7");


$this->info("Done!");

})->describe('Duplicate Plank Energy Account IDs');

Artisan::command('515:update-energy-account-approved-date', function () {
// 2021-06-26
    DB::statement("update cm_energy_account_logs set created_at = '2021-06-25 09:27:33' where current_status = 4 and energy_account_id=44401;");
    DB::statement("update cm_energy_account_logs set created_at = '2021-06-25 09:27:34' where current_status = 4 and energy_account_id=44407;");
    DB::statement("update cm_energy_account_logs set created_at = '2021-06-25 09:44:59' where current_status = 4 and energy_account_id=44398;");
    DB::statement("update cm_energy_account_logs set created_at = '2021-06-25 09:44:59' where current_status = 4 and energy_account_id=44404;");
    DB::statement("update cm_energy_account_logs set created_at = '2021-06-27 04:38:47' where current_status = 4 and energy_account_id=44758;");
    DB::statement("update cm_energy_account_logs set created_at = '2021-06-27 04:38:47' where current_status = 4 and energy_account_id=45100;");

$this->info("Done!");

})->describe('https://3.basecamp.com/3526928/buckets/15863659/todos/3167012669#__recording_3883709189');

Artisan::command('515:manual-payout-adjustment-jul1', function () {
// 2021-07-01

	/*
    DB::statement("UPDATE cm_payments cp SET cp.other_income = 100 WHERE cp.user_id = 3081 AND cp.created_date = '2021-06-21';");
    DB::statement("INSERT INTO cm_commission_payouts (commission_period_id, user_id,sponsor_id,payee_id,commission_value,percent,amount,remarks,is_paid,currency)
					SELECT 
					  ccp.id AS commperiodId, 
					  3081 user_id,  
					  3081 sponsor_id, 
					  3081 payee_id,
					  100 cv,
					  100 percent,
					  100 amount,
					  'Manual Adjustment' remarks,
					  1 is_paid ,
					  'EUR' currency
					FROM cm_commission_periods ccp WHERE ccp.is_locked = 1 AND ccp.end_date = '2021-06-20' AND ccp.commission_type_id = 7;");
	*/

	/*				
	DB::statement("UPDATE cm_payments cp SET cp.other_income = 500 WHERE cp.user_id = 13487 AND cp.created_date = '2021-06-21';");
    DB::statement("INSERT INTO cm_commission_payouts (commission_period_id, user_id,sponsor_id,payee_id,commission_value,percent,amount,remarks,is_paid,currency)
					SELECT 
				  ccp.id AS commperiodId, 
				  13487 user_id,  
				  13487 sponsor_id, 
				  13487 payee_id,
				  500 cv,
				  100 percent,
				  500 amount,
				  'Manual Adjustment' remarks,
				  1 is_paid ,
				  'EUR' currency
				FROM cm_commission_periods ccp WHERE ccp.is_locked = 1 AND ccp.end_date = '2021-06-20' AND ccp.commission_type_id = 7;");
	*/

	DB::statement("UPDATE cm_payments cp SET cp.other_income = 250, total_gross = 395, year_to_date_gross = 395 WHERE cp.user_id = 26741 AND cp.created_date = '2021-06-28'");	
			
	//DB::statement("UPDATE cm_commission_payouts ccp SET ccp.amount = 250, ccp.commission_value = 250 WHERE ccp.payee_id = 26741 AND ccp.commission_period_id = 265 LIMIT 1");	

$this->info("Done!");

})->describe('https://3.basecamp.com/3526928/buckets/15863659/todos/3167012669#__recording_3911540062');

Artisan::command('515:import-migrated-associates-sept13', function () {
	
DB::statement("
CREATE TABLE migrated_associates_as_of_sept13 (
  AssociateID VARCHAR(255),
  SponsorID VARCHAR(255),
  PlankAssociateID VARCHAR(255),
  FirstName VARCHAR(255),
  MiddleName VARCHAR(255),
  LastName VARCHAR(255),
  DateEnrolled DATETIME,
  Active VARCHAR(255),
  `Terminated` VARCHAR(255),
  Email VARCHAR(255),
  MobileNumber VARCHAR(255),
  PhoneNumber VARCHAR(255),
  Address VARCHAR(255),
  City VARCHAR(255),
  State VARCHAR(255),
  Country VARCHAR(255),
  Zip VARCHAR(255),
  CF VARCHAR(255),
  `P IVA` VARCHAR(255),
  `CODICE SDI` VARCHAR(255),
  PEC VARCHAR(255),
  IBAN VARCHAR(255),
  BillDay VARCHAR(255),
  birth_date DATETIME,
  birth_city VARCHAR(255),
  `Birth Province` VARCHAR(255),
  `Birth Country` VARCHAR(255),
  `Citizenship Country` VARCHAR(255),
  `Civil Status` VARCHAR(255),
  `Language Spoken` VARCHAR(255)
)
ENGINE = INNODB
AVG_ROW_LENGTH = 8192
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci
ROW_FORMAT = DYNAMIC;


");

DB::statement("
INSERT INTO migrated_associates_as_of_sept13 VALUES
('26852', '26807', 'N26852', 'Kimi', NULL, 'Fischer ', '2021-09-01 00:00:00', 'Yes', 'No', 'kimifischer2@gmail.com', '41795600658', NULL, 'Via  Locarno 10', 'Ascona', 'Ticino', 'Switzerland', '6612', '7563506555540', NULL, NULL, NULL, 'CH4304835124801540000', NULL, '2001-10-14 00:00:00', 'Davos', 'Grigioni ', 'Switzerland', 'Switzerland', 'Single', 'Italian'),
('26855', '14964', 'N26855', 'Monica', NULL, 'Baccarlino', '2021-09-09 00:00:00', 'Yes', 'No', 'bacm79@libero.it', '3396053673', NULL, 'VIALE Vittorio Veneto  24', 'Borgosesia', 'VC', 'Italy', '13011', 'BCCMNC79D45B041T', NULL, NULL, NULL, 'IT05Y0301503200000003400400', NULL, '1979-04-05 00:00:00', 'Borgosesia', 'VC', 'Italy', 'Italy', 'Not Disclosed', 'Italian');

 ");

$this->info("Done!");

})->describe('Import Migrated Associates to new table');

Artisan::command('515:run-update-enrollmentdate', function () {
	
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-02' WHERE user_id = 26769;");
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-02' WHERE user_id = 26770;");
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-03' WHERE user_id = 26771;");
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-06' WHERE user_id = 26772;");
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-06' WHERE user_id = 26773;");
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-06' WHERE user_id = 26774;");
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-07' WHERE user_id = 26775;");
DB::statement("UPDATE cm_affiliates SET affiliated_at = '2021-07-08' WHERE user_id = 26776;");

$this->info("Done!");

})->describe('Import Migrated Associates to new table');


Artisan::command('515:update-query', function () {

// Done: 2021-07-19

// -- Last Update is Cancelled but has no status log for Cancelled
/*
DB::statement("
    INSERT INTO cm_energy_account_logs(created_at, updated_at, energy_account_id, current_status, customer_id, reference_id)
    SELECT 
        statusAPI.created_at AS lastApiUpdate_date,
        statusAPI.created_at AS lastApiUpdate_date,
        cea.id,
        7,
        cea.customer_id,
        cea.reference_id
        FROM cm_energy_accounts cea
      LEFT JOIN cm_energy_account_logs stat1 ON stat1.energy_account_id = cea.id AND stat1.current_status = 1
      LEFT JOIN cm_energy_account_logs stat2 ON stat2.energy_account_id = cea.id AND stat2.current_status = 2
      LEFT JOIN cm_energy_account_logs stat3 ON stat3.energy_account_id = cea.id AND stat3.current_status = 3
      LEFT JOIN cm_energy_account_logs stat4 ON stat4.energy_account_id = cea.id AND stat4.current_status = 4
      LEFT JOIN cm_energy_account_logs stat5 ON stat5.energy_account_id = cea.id AND stat5.current_status = 5
      LEFT JOIN cm_energy_account_logs stat6 ON stat6.energy_account_id = cea.id AND stat6.current_status = 6
      LEFT JOIN cm_energy_account_logs stat7 ON stat7.energy_account_id = cea.id AND stat7.current_status = 7
      LEFT JOIN cm_energy_account_status_logs statusAPI ON statusAPI.plank_energy_account_id = cea.plank_energy_account_id
      AND statusAPI.id = (SELECT ceasl.id FROM cm_energy_account_status_logs ceasl WHERE ceasl.plank_energy_account_id = cea.plank_energy_account_id ORDER BY ceasl.created_at DESC LIMIT 1)
      WHERE statusAPI.status_type IN (SELECT ceastd.type FROM cm_energy_account_status_types_details ceastd WHERE ceastd.parent_status_type = 7)
      AND cea.status != 7 AND stat7.created_date IS NULL;
");
$this->info("Done 1!");

// -- Has Cancelled in log but no longer Cancelled or not yet Cancelled
DB::statement("
    DELETE FROM cm_energy_account_logs WHERE current_status = 7
    AND energy_account_id IN 
    (SELECT 
    cea.id
        FROM cm_energy_accounts cea
      LEFT JOIN cm_energy_account_logs stat1 ON stat1.energy_account_id = cea.id AND stat1.current_status = 1
      LEFT JOIN cm_energy_account_logs stat2 ON stat2.energy_account_id = cea.id AND stat2.current_status = 2
      LEFT JOIN cm_energy_account_logs stat3 ON stat3.energy_account_id = cea.id AND stat3.current_status = 3
      LEFT JOIN cm_energy_account_logs stat4 ON stat4.energy_account_id = cea.id AND stat4.current_status = 4
      LEFT JOIN cm_energy_account_logs stat5 ON stat5.energy_account_id = cea.id AND stat5.current_status = 5
      LEFT JOIN cm_energy_account_logs stat6 ON stat6.energy_account_id = cea.id AND stat6.current_status = 6
      LEFT JOIN cm_energy_account_logs stat7 ON stat7.energy_account_id = cea.id AND stat7.current_status = 7
      LEFT JOIN cm_energy_account_status_logs statusAPI ON statusAPI.plank_energy_account_id = cea.plank_energy_account_id
      AND statusAPI.id = (SELECT ceasl.id FROM cm_energy_account_status_logs ceasl WHERE ceasl.plank_energy_account_id = cea.plank_energy_account_id ORDER BY ceasl.created_at DESC LIMIT 1)
      WHERE statusAPI.status_type NOT IN (SELECT ceastd.type FROM cm_energy_account_status_types_details ceastd WHERE ceastd.parent_status_type = 7)
      AND stat7.id IS NOT NULL
      AND statusAPI.id IS NOT NULL
    AND NOT EXISTS(SELECT 1 FROM cm_energy_account_cancellation ceac WHERE ceac.plank_energy_account_id = cea.plank_energy_account_id AND ceac.cancellation_date <= CURRENT_DATE())
    AND cea.reference_id NOT LIKE '%test%');
");
$this->info("Done 2!");

// -- Fix current status based on status logs
DB::statement("
    UPDATE cm_energy_accounts cea1 
      JOIN (
        SELECT 
          cea.id,
          stat1.created_date AS pendingConfirmation,
          stat2.created_date AS pendingApproval,
          stat3.created_date AS pendingRejection,
          stat4.created_date AS approved,
          stat5.created_date AS flowing,
          stat6.created_date AS flowingPending,
          stat7.created_date AS cancelled,
          cea.status,
          (SELECT statLogs.status_type 
          FROM cm_energy_account_status_logs statLogs 
          WHERE statLogs.plank_energy_account_id = cea.plank_energy_account_id 
          ORDER BY statLogs.created_at DESC LIMIT 1) lastApiStatus,
          cea.plank_energy_account_id
          FROM cm_energy_accounts cea
        LEFT JOIN cm_energy_account_logs stat1 ON stat1.energy_account_id = cea.id AND stat1.current_status = 1
        LEFT JOIN cm_energy_account_logs stat2 ON stat2.energy_account_id = cea.id AND stat2.current_status = 2
        LEFT JOIN cm_energy_account_logs stat3 ON stat3.energy_account_id = cea.id AND stat3.current_status = 3
        LEFT JOIN cm_energy_account_logs stat4 ON stat4.energy_account_id = cea.id AND stat4.current_status = 4
        LEFT JOIN cm_energy_account_logs stat5 ON stat5.energy_account_id = cea.id AND stat5.current_status = 5
        LEFT JOIN cm_energy_account_logs stat6 ON stat6.energy_account_id = cea.id AND stat6.current_status = 6
        LEFT JOIN cm_energy_account_logs stat7 ON stat7.energy_account_id = cea.id AND stat7.current_status = 7
        -- HAVING lastApiStatus IS NULL
      ) result ON result.id = cea1.id

    SET cea1.status = (
        CASE
          WHEN result.cancelled IS NOT NULL THEN 7
          WHEN result.flowingPending IS NOT NULL THEN 6
          WHEN result.flowing IS NOT NULL THEN 5
          WHEN result.approved IS NOT NULL THEN 4
          WHEN result.pendingRejection IS NOT NULL THEN 3
          WHEN result.pendingApproval IS NOT NULL THEN 2
          WHEN result.pendingConfirmation IS NOT NULL THEN 1
        END
    );
");


$this->info("Done 3!");



DB::statement("UPDATE cm_energy_accounts cea SET cea.status = 5 WHERE cea.reference_id = 'IT001E44378387';");
DB::statement("DELETE FROM cm_energy_account_logs WHERE energy_account_id = 30745 AND current_status = 6;");


DB::statement("UPDATE users u SET u.memberid = CONCAT('N',u.id) WHERE u.migrated_user_id IN (SELECT AssociateID FROM migrated_associates_as_of_aug23)");

*/


// Done on 2021-09-04: https://3.basecamp.com/3526928/buckets/19336458/todos/4103672821#__recording_4114275605

/*
DB::statement("UPDATE cm_commission_payouts ccp SET ccp.is_paid = 1, ccp.commission_period_id = 337 WHERE ccp.id IN (9509443,9509440,9509442,9509441);");
DB::statement("UPDATE cm_commission_payouts ccp SET ccp.amount = 150 WHERE ccp.id IN (9509441);");
DB::statement("INSERT INTO cm_payment_details(payment_id, payout_id) VALUES (8165,9509443)");
DB::statement("INSERT INTO cm_payment_details(payment_id, payout_id) VALUES (8179,9509440);");
DB::statement("INSERT INTO cm_payment_details(payment_id, payout_id) VALUES (8195,9509442);");
DB::statement("INSERT INTO cm_payment_details(payment_id, payout_id) VALUES (8198,9509441);");
DB::statement("UPDATE cm_payments cp SET cp.other_income = 5, cp.total_gross = 90, cp.amount = 90 WHERE cp.user_id = 18679 AND cp.id = 8165");
DB::statement("UPDATE cm_payments cp SET cp.other_income = 190, cp.total_gross = 343, cp.amount = 343 WHERE cp.user_id = 24246 AND cp.id = 8179");
DB::statement("UPDATE cm_payments cp SET cp.other_income = 20, cp.total_gross = 40, cp.amount = 40 WHERE cp.user_id = 26705 AND cp.id = 8195");
DB::statement("UPDATE cm_payments cp SET cp.other_income = 150, cp.total_gross = 270, cp.amount = 270 WHERE cp.user_id = 26729 AND cp.id = 8198");
DB::statement("UPDATE cm_commission_payouts ccp SET ccp.is_paid = 0 WHERE ccp.payee_id IN (23036,26036) AND ccp.commission_period_id=334;");
DB::statement("DELETE FROM cm_payment_details  WHERE payout_id IN (SELECT ccp.id FROM cm_commission_payouts ccp WHERE ccp.payee_id IN (23036,26036) AND ccp.commission_period_id=334);");
DB::statement("DELETE FROM cm_payments WHERE user_id IN (23036,26036) AND created_date='2021-08-31';");
DB::statement("UPDATE cm_payments cp SET cp.total_net_amount = 343 WHERE cp.user_id = 24246 AND cp.id = 8179");
*/

// Done on 2021-09-09 https://3.basecamp.com/3526928/buckets/19336458/todos/4126865248#__recording_4134295564
//DB::statement("UPDATE cm_energy_account_status_types_details ceastd SET ceastd.parent_status_type = 7 WHERE ceastd.type=7 LIMIT 1;");

// Done on 2021-09-14 https://3.basecamp.com/3526928/buckets/19336458/comments/4147557663
DB::statement("UPDATE cm_energy_account_status_types_details ceastd SET ceastd.parent_status_type = 6 WHERE ceastd.type=7 LIMIT 1;");

$this->info("Done!");

})->describe('Manual Update');



Artisan::command('515:update-energy-account-status-20210912', function () {

	try {
		


		$date_created_condition = date("Y-m-d H:i:s");
		$current_date           = date("Y-m-d");
	
		$this->info('date = ' . $current_date);
		
	
		$eneryCountStatus = DB::table("energy_account_status_sept13 AS ecs")
			->orderBy("ecs.plankEnergyAccountId")
			->get();
	
		foreach($eneryCountStatus as $ecs){
	
			$plankEnergyAccountLog = DB::table('cm_energy_account_status_logs')
				->select('status_type')
				->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
				->where('created_at', '>=', '2021-09-12')	// '2021-09-12'
				->orderByDesc('created_at')
				->limit(1)
				->get();
	
			$energyAccount = DB::table('cm_energy_accounts')
				->select('id', 'plank_energy_account_id', 'customer_id', 'reference_id')
				->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
				//->where('created_at', '>=',  $date_created_condition)
				->orderByDesc('created_at')
				->first();
	
			$plankEnergyAccountLog = count($plankEnergyAccountLog);
	
			//STATUS LIKE "%2"
			if( substr($ecs->status, -1) == '2' && empty($plankEnergyAccountLog) ){
				$this->info('plankEnergyAccountId = ' . $ecs->plankEnergyAccountId . ' Update cm_energy_accounts [status = 2]');
				//Update cm_energy_accounts [status = 2]
				DB::table('cm_energy_accounts')
					->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
					->update(['status' => 2])
				;
	
				//Delete from cm_energy_account_logs where status >= 3
				if( $energyAccount ){
					DB::table('cm_energy_account_logs')
						->where('current_status', '>=', 3)
						->where('energy_account_id', $energyAccount->id)
						->delete()
					;
				}            
			}
	
			//STATUS LIKE "%4"
			if( substr($ecs->status, -1) == '4' ){
			   //no change
			}
	
			//STATUS LIKE "%5"
			if( substr($ecs->status, -1) == '5' ){
				if( $ecs->date_starts_flowing <= $current_date && empty($plankEnergyAccountLog) ){
					$this->info('plankEnergyAccountId = ' . $ecs->plankEnergyAccountId . ' Update cm_energy_accounts [status = 5]');
					//Update cm_energy_accounts [status = 5]
					DB::table('cm_energy_accounts')
						->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
						->update(['status' => 5])
					;
					//Delete from cm_energy_account_logs where status >= 6
					if( $energyAccount ){
						DB::table('cm_energy_account_logs')
							->where('current_status', '>=', 6)
							->where('energy_account_id', $energyAccount->id)
							->delete()
						;
					}
				} 
	
				if( $ecs->date_starts_flowing > $current_date ){
					$this->info('plankEnergyAccountId = ' . $ecs->plankEnergyAccountId . ' Update cm_energy_accounts [status = 4]');
					//Update cm_energy_accounts [status = 4]
					DB::table('cm_energy_accounts')
						->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
						->update(['status' => 4])
					;
					//Delete from cm_energy_account_logs where status >= 5
					if( $energyAccount ){
						DB::table('cm_energy_account_logs')
							->where('current_status', '>=', 5)
							->where('energy_account_id', $energyAccount->id)
							->delete()
						;
					}
				}
			}
	
			//STATUS LIKE "%7"
			if( substr($ecs->status, -1) == '7' ){
				if( $ecs->date_stops_flowing <= $current_date && empty($plankEnergyAccountLog) ){
					$this->info('plankEnergyAccountId = ' . $ecs->plankEnergyAccountId . ' Update cm_energy_accounts [status = 7]');
					//Update cm_energy_accounts [status = 7]
					DB::table('cm_energy_accounts')
						->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
						->update(['status' => 7])
					;
					//Insert to cm_energy_account_logs [status = 7, created_at = :date_starts_flowing]
					/*
					DB::table('cm_energy_account_logs')->insert([
						'current_status' => 7,
						'created_at' => $ecs->date_starts_flowing,
						'energy_account_id' => $energyAccount->id,
						'notes' => 'migration-sept12',
						'customer_id' => $energyAccount->customer_id,
						'reference_id' => $energyAccount->reference_id
					]);
					*/

					if ($energyAccount) {
						DB::statement("
						INSERT INTO cm_energy_account_logs (
							current_status,
							created_at,
							energy_account_id,
							notes,
							customer_id,
							reference_id
						)
						VALUES(
							7,
							'$ecs->date_starts_flowing',
							$energyAccount->id,
							'migration-sept12',
							$energyAccount->customer_id,
							'$energyAccount->reference_id'
						)
						ON DUPLICATE KEY UPDATE
							current_status = VALUES(current_status),
							created_at = '$ecs->date_starts_flowing',
							energy_account_id = VALUES(energy_account_id),
							notes = 'migration-sept12',
							customer_id = VALUES(customer_id),
							reference_id = VALUES(reference_id)
						;
					");
					}
				}
	
				if( isset($ecs->date_stops_flowing) && empty($plankEnergyAccountLog) ){
					$this->info('plankEnergyAccountId = ' . $ecs->plankEnergyAccountId . ' Update cm_energy_accounts [status = 7]');
					//Update cm_energy_accounts [status = 7]
					DB::table('cm_energy_accounts')
						->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
						->update(['status' => 7])
					;
					//Insert to cm_energy_account_logs [status = 7, created_at = NOW()]
					/*
					DB::table('cm_energy_account_logs')->insert([
						'current_status' => 7,
						'created_at' => date("Y-m-d H:i:s"),
						'energy_account_id' => $energyAccount->id,
						'notes' => 'migration-sept12',
						'customer_id' => $energyAccount->customer_id,
						'reference_id' => $energyAccount->reference_id
					]);
					*/
	
					if ($energyAccount) {
						DB::statement("
							INSERT INTO cm_energy_account_logs (
								current_status,
								created_at,
								energy_account_id,
								notes,
								customer_id,
								reference_id
							)
							VALUES(
								7,
								NOW(),
								$energyAccount->id,
								'migration-sept12',
								$energyAccount->customer_id,
								'$energyAccount->reference_id'
							)
							ON DUPLICATE KEY UPDATE
								current_status = VALUES(current_status),
								created_at = NOW(),
								energy_account_id = VALUES(energy_account_id),
								notes = 'migration-sept12',
								customer_id = VALUES(customer_id),
								reference_id = VALUES(reference_id)
							;
						");
					}
				}
	
				if( $ecs->date_stops_flowing > $current_date ){
					$this->info('plankEnergyAccountId = ' . $ecs->plankEnergyAccountId . ' Update cm_energy_accounts [status = 6]');
					//Update cm_energy_accounts [status = 6]
					DB::table('cm_energy_accounts')
						->where('plank_energy_account_id', $ecs->plankEnergyAccountId)
						->update(['status' => 6])
					;
					//Delete from cm_energy_account_logs where status = 7
					if( $energyAccount ){
						DB::table('cm_energy_account_logs')
							->where('current_status', 7)
							->where('energy_account_id', $energyAccount->id)
							->delete()
						;
					}
				}
			}
		}
	
		$this->info("Done!");
	}catch (\Exception $ex) {
		if(strpos($ex->getMessage(), 'Lock wait timeout exceeded') === false && strpos($ex->getMessage(), 'Deadlock found') === false) {
			throw $ex;
		}
		
		$this->info($ex->getMessage());
	}
})->describe('Updated energy account status using energy_account_statuses_as_of_sept12');