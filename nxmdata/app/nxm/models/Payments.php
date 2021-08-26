<?php

namespace App\nxm\models;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Capsule\Manager as DB;

class Payments extends Eloquent
{
    protected $table = 'cm_payments';
    public $timestamps = false;

    private function doMigration() {

        DB::update('
            CREATE TABLE IF NOT EXISTS `jez`.`cm_payments` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `payee` VARCHAR(256) DEFAULT \'\' COMMENT \'Who will receive payment .\',
                `payer` VARCHAR(256) DEFAULT \'\' COMMENT \'The source of payment the token depends on mode_of_payment.\',
                `remarks` VARCHAR(1024) DEFAULT \'\' COMMENT \'Additional details describing the payment.\',
                `mode_of_payment` ENUM(\'HYPERWALLET\') DEFAULT \'HYPERWALLET\' COMMENT \'Payment gateway where the amount is sent\',
                `transaction_no` VARCHAR(256) DEFAULT NULL COMMENT \'Unique number from gateway to denote payment is successful.\',
                `prepared_by` INT(11) DEFAULT NULL COMMENT \'The one who made the transaction.\',
                `is_cancelled` VARCHAR(3) DEFAULT \'N\' COMMENT \'Y or N We will give the admin the ability to cancel payment or resend until successful.\',
                `deleted_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(`id`)
            ) ENGINE = InnoDB;
        ');

        DB::update('
            CREATE TABLE IF NOT EXISTS `jez`.`cm_payment_details`(
                `id` INT(11) NOT NULL,
                `payment_id` INT(11) NOT NULL,
                `reference_no` VARCHAR(256) DEFAULT \'\' COMMENT \'The detailed payment source.\',
                `amount` DECIMAL(11,2) DEFAULT \'.00\' COMMENT \'000000000.00\',
                `deleted_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT PK_payment_details PRIMARY KEY(id, payment_id)
            ) ENGINE = InnoDB;
        ');
    }

    public function getNewORNumber() {

        $this->doMigration();
        $result = $this->selectRaw('MAX(`id`) AS `id`')->get()->toArray();
        if (isset($result[0]['id'])) {

            return (int)$result[0]['id'] + 1;
        } else {

            return 1;
        }
    }

    public function getTotal() {

        $result = PaymentDetails::whereRaw('payment_id = ?', array($this->id))->selectRaw('SUM(amount) AS amount')->get()->toArray();
        if (isset($result[0]['amount'])) {
            return $result[0]['amount'];
        } else {
            return 0;
        }
    }
}