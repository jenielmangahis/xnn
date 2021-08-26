<?php

namespace App\nxm\models;

class PropayAccount
{
    private $_debug;
    private $proPayApiUrl;

    public function __construct($debug = false) {
        $this->_debug = $debug;
        if ($this->_debug) {

            $this->proPayApiUrl = 'https://xmltest.propay.com/API/PropayAPI.aspx';
        } else {

            $this->proPayApiUrl = 'https://epay.propay.com/API/PropayAPI.aspx';
        }
    }
    public function getPropayApiUrl() {
        return $this->proPayApiUrl;
    }
    public function getClassType() {
        return 'partner';
    }
    public function getSignupCert() {

        /*
        ProPay API credential for account boarding:
        certStr = b3030b21e9945aa9d08755b1e77b94
        termId = b1e77b94
        */
        if ($this->_debug) {

            return '3bb8578535b414ea85aec4d7700e11';
        } else {

            return 'b3030b21e9945aa9d08755b1e77b94';
        }
    }
    public function getSignupTermId() {
        if ($this->_debug) {
            return '700e11';
        } else {
            return 'b1e77b94';
        }
    }
    public function getDisbursementCert() {

        if ($this->_debug) {

            return '3bb8578535b414ea85aec4d7700e11';
        } else {

            return 'f6134f9eaa74f94b3925ba86060254';
        }
    }
    public function getDisbursementTermId() {

        if ($this->_debug) {

            return '700e11';
        } else {

            return 'ba86060254';
        }
    }
    public function getStatusMessage($code) {

        $message = '';
        switch($code) {
            case '00':
                $message = 'Success';
                break;
            case '20':
                $message = 'Invalid user name.';
                break;
            case '21':
                $message = 'Invalid trans type or service not allowed';
                break;
            case '23':
                $message = 'Invalid account type';
                break;
            case '24':
                $message = 'Invalid source email';
                break;
            case '25':
                $message = 'Invalid first name';
                break;
            case '26':
                $message = 'Invalid middle initial';
                break;
            case '27':
                $message = 'Invalid last name';
                break;
            case '28':
                $message = 'Invalid address!';
                break;
            case '29':
                $message = 'Invalid apartment number.';
                break;
            case '30':
                $message = 'Invalid city.';
                break;
            case '31':
                $message = 'Invalid state.';
                break;
            case '32':
                $message = 'Invalid Zip Code';
                break;
            case '33':
                $message = 'Invalid mailing address';
                break;
            case '34':
                $message = 'Invalid mailApt';
                break;
            case '35':
                $message = 'Invalid mailing city';
                break;
            case '36':
                $message = 'Invalid mailState';
                break;
            case '37':
                $message = 'Invalid mailZip';
                break;
            case '38' : $message = 'Invalid day phone';
                break;
            case '39':
                $message = 'Invalid evening phone';
                break;
            case '40':
                $message = 'Invalid social security number';
                break;
            case '41':
                $message = 'Invalid date of birth.';
                break;
            case '42':
                $message = 'Invalid receiver email.';
                break;
            case '43':
                $message = 'Invalid known account';
                break;
            case '44':
                $message = 'Invalid amount';
                break;
            case '45':
                $message = 'Invalid invNum';
                break;
            case '46':
                $message = 'Invalid rtNum';
                break;
            case '47':
                $message = 'Invalid account number';
                break;
            case '48':
                $message = 'Invalid Credit Card Number';
                break;
            case '49':
                $message = 'Invalid Expiration Date';
                break;
            case '50':
                $message = 'Invalid cvv2';
                break;
            case '51':
                $message = 'Invalid transNum';
                break;
            case '52' :
                $message = 'Invalid splitNum';
                break;
            case '53' :
                $message = 'A ProPay account with this e-mail address already exists or User has no AccountNumber';
                break;
            case '54':
                $message = 'A ProPay account with this social security number already exists';
                break;
            case '55':
                $message = 'Recipient’s e-mail address should have a ProPay account and doesn’t';
                break;
            case '56':
                $message = 'Recipient’s e-mail address shouldn’t have a ProPay account and does';
                break;
            case '57':
                $message = 'Cannot settle transaction because it already expired';
                break;
            case '58':
                $message = 'Credit card declined';
                break;
            case '59':
                $message = 'User not authenticated';
                break;
            case '60':
                $message = 'Credit card authorization timed out; retry at a later time';
                break;
            case '61':
                $message = 'Amount exceeds single transaction limit';
                break;
            case '62':
                $message = 'Amount exceeds monthly volume limit';
                break;
            case '63' :
                $message = 'Insufficient funds in account';
                break;
            case '64' :
                $message = 'Over credit card use limit';
                break;
            case '65' :
                $message = 'Miscellaneous error ';
                break;
            case '66' :
                $message = 'Denied a ProPay account';
                break;
            case '67':
                $message = 'Unauthorized service requested';
                break;
            case '68':
                $message = 'Account not affiliated';
                break;
            case '69':
                $message = 'Duplicate invoice number (Transaction succeeded in a prior attempt within the previous 24 hours.)';
                break;
            case '70':
                $message = 'Duplicate external ID';
                break;
            case '71':
                $message = 'Account previously set up, but problem affiliating it with partner';
                break;
            case '72':
                $message = 'The ProPay Account has already been upgraded to a Premium Account';
                break;
            case '73' :
                $message = 'Invalid Destination Account';
                break;
            case '74' :
                $message = 'Account or Trans Error';
                break;
            case '75' :
                $message = 'Money already pulled';
                break;
            case '76' :
                $message = 'Not Premium (used only for push/pull transactions) ';
                break;
            case '77' :
                $message = 'Empty results';
                break;
            case '78' :
                $message = 'Invalid Authentication';
                break;
            case '79' : $message = 'Generic account status error';
                break;
            case '80':
                $message = 'Invalid Password';
                break;
            case '81':
                $message = 'AccountExpired';
                break;
            case '82':
                $message = 'InvalidUserID';
                break;
            case '83':
                $message = 'BatchTransCountError';
                break;
            case '84':
                $message = 'InvalidBeginDate';
                break;
            case '85':
                $message = 'InvalidEndDate';
                break;
            case '86':
                $message = 'InvalidExternalID';
                break;
            case '87':
                $message = 'DuplicateUserID';
                break;
            case '88':
                $message = 'Invalid track 1';
                break;
            case '89':
                $message = 'Invalid track 2';
                break;
            case '90':
                $message = 'Transaction already refunded';
                break;
            case '91':
                $message = 'Duplicate Batch ID';
                break;
            case '92':
                $message = 'Duplicate Batch Transaction';
                break;
            case '93':
                $message = 'Batch Transaction amount error';
                break;
            case '94':
                $message = 'Unavailable Tier';
                break;
            case '95':
                $message = 'Invalid Country Code';
                break;
            case '97':
                $message = 'Account created in documentary status, but still must be validated.';
                break;
            case '98':
                $message = 'Account created in documentary status, but still must be validated and paid for.';
                break;
            case '99':
                $message = 'Account created successfully, but still must be paid for.';
                break;
            case '100':
                $message = 'Transaction Already Refunded';
                break;
            case '101':
                $message = 'Refund Exceeds Original Transaction';
                break;
            case '102':
                $message = 'Invalid Payer Name';
                break;
            case '103':
                $message = 'Transaction does not meet date criteria';
                break;
            case '104':
                $message = 'Transaction could not be refunded due to current transaction state.';
                break;
            case '105':
                $message = 'Direct deposit account not specified';
                break;
            case '106':
                $message = 'Invalid SEC code';
                break;
            case '107':
                $message = 'Invalid Account Name (ACH account)';
                break;
            case '108':
                $message = 'Invalid x509 certificate';
                break;
            case '109':
                $message = 'Invalid value for require CC refund';
                break;
            case '110':
                $message = 'Required field is missing (This is returned only for edit ProPay account. See response tag for field name.)';
                break;
            case '111':
                $message = 'Invalid EIN';
                break;
            case '112':
                $message = 'Invalid business legal name (DBA)';
                break;
            case '113':
                $message = 'One of the business legal address fields is invalid';
                break;
            case '114':
                $message = 'Business (legal) city is invalid';
                break;
            case '115':
                $message = 'Business (legal) state is invalid';
                break;
            case '116':
                $message = 'Business (legal) zip is invalid';
                break;
            case '117':
                $message = 'Business (legal) country is invalid';
                break;
            case '118':
                $message = 'Mailing address invalid';
                break;
            case '119':
                $message = 'Business (legal) address is invalid';
                break;
            case '120':
                $message = 'Incomplete business address';
                break;
            case '121':
                $message = 'Amount Encumbered by enhanced Spendback';
                break;
            case '122':
                $message = 'Invalid encrypting device type';
                break;
            case '123':
                $message = 'Invalid key serial number';
                break;
            case '124':
                $message = 'Invalid encrypted track data';
                break;
            case '125':
                $message = 'You may not transfer money between these two accounts. Sponsor bank transfer disallowed.';
                break;
            case '126':
                $message = 'Currency code not allowed for this transaction';
                break;
            case '127':
                $message = 'Currency code not permitted for this account';
                break;
        }

        return $message;
    }
}