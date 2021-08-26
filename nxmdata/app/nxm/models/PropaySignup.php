<?php

namespace App\nxm\models;
use \Illuminate\Database\Capsule\Manager as DB;

class PropaySignup extends PropayAccount {
    static $xml;
    private $_xml_sent;
    private $_xml_return;
    private $_user_id;
    private $_transType;
    private $_sourceEmail;
    private $_firstName;
    private $_lastName;
    private $_addr;
    private $_aptNum;
    private $_city;
    private $_state;
    private $_zip;
    private $_country;
    private $_dayPhone;
    private $_evenPhone;
    private $_externalId;
    private $_ssn;
    private $_dob;
    private $_tier;

    public function __construct($debug = false) {
        parent::__construct($debug);
    }
    public function setUserId($user_id) {
        $this->_user_id = $user_id;
        return $this;
    }
    public function setTransType($transType) {
        $this->_transType = $transType;
        return $this;
    }
    public function setSourceEmail($sourceEmail) {
        $this->_sourceEmail = $sourceEmail;
        return $this;
    }
    public function setFirstName($firstName) {
        $this->_firstName = $firstName;
        return $this;
    }
    public function setLastName($lastName) {
        $this->_lastName = $lastName;
        return $this;
    }
    public function setAddr($addr) {
        $this->_addr = $addr;
        return $this;
    }
    public function setAptNum($aptNum) {
        $this->_aptNum = $aptNum;
        return $this;
    }
    public function setCity($city) {
        $this->_city = $city;
        return $this;
    }
    public function setState($state) {
        $this->_state = $state;
        return $this;
    }
    public function setZip($zip) {
        $this->_zip = $zip;
        return $this;
    }
    public function setCountry($country) {
        $this->_country = $country;
        return $this;
    }
    public function setDayPhone($dayPhone) {
        $this->_dayPhone = $dayPhone;
        return $this;
    }
    public function setEvenPhone($evenPhone) {
        $this->_evenPhone = $evenPhone;
        return $this;
    }
    public function setExternalId($externalId) {
        $this->_externalId = $externalId;
        return $this;
    }
    public function setssn($ssn) {
        $this->_ssn = $ssn;
        return $this;
    }
    public function setDob($dob) {
        $this->_dob = $dob;
        return $this;
    }
    public function setTier($tier) {
        $this->_tier = $tier;
        return $this;
    }
    public function fetchSigup() {

        self::$xml = new \SimpleXMLElement('<?xml version="1.0"?><!DOCTYPE Request.dtd><XMLRequest></XMLRequest>');
        self::$xml->addChild('certStr', $this->getSignupCert());
        self::$xml->addChild('termid', $this->getSignupTermId());
        self::$xml->addChild('class', $this->getClassType());

        $xmlTrans = self::$xml->addChild('XMLTrans');
        $xmlTrans->addChild('transType', 13);
        $xmlTrans->addChild('sourceEmail', $this->_sourceEmail);
        $xmlRequest = self::$xml->asXML();

        $response = $this->sendRequest($xmlRequest);
        $xmlResponse = simplexml_load_string($response);

        $this->_xml_sent = (string)$xmlRequest;
        $this->_xml_return = (string)$response;

        $id = $this->log(array(
            'user_id' => $this->_user_id,
            'xml_sent' => $this->_xml_sent,
            'xml_return' => $this->_xml_return
        ));

        if ((string)$xmlResponse->XMLTrans->status === '00') {

            $xmlAccount = $xmlResponse;
            $data = array(
                'user_id' => $this->_user_id,
                'transType' => (string)$xmlAccount->XMLTrans->transType,
                'sourceEmail' => (string)$xmlAccount->XMLTrans->sourceEmail,
                'accountNum' => (string)$xmlAccount->XMLTrans->accountNum,
                'tier' => (string)$xmlAccount->XMLTrans->tier,
                'expiration' => (string)$xmlAccount->XMLTrans->expiration,
                'signupDate' => (string)$xmlAccount->XMLTrans->signupDate,
                'affiliation' => (string)$xmlAccount->XMLTrans->affiliation,
                'accntStatus' => (string)$xmlAccount->XMLTrans->accntStatus,
                'addr' => (string)$xmlAccount->XMLTrans->addr,
                'city' => (string)$xmlAccount->XMLTrans->city,
                'state' => (string)$xmlAccount->XMLTrans->state,
                'zip' => (string)$xmlAccount->XMLTrans->zip,
                'status' => (string)$xmlAccount->XMLTrans->status,
                'apiReady' => (string)$xmlAccount->XMLTrans->apiReady,
                'currencyCode' => (string)$xmlAccount->XMLTrans->currencyCode,
                'CreditCardTransactionLimit' => (string)$xmlAccount->XMLTrans->CreditCardTransactionLimit,
                'CreditCardMonthLimit' => (string)$xmlAccount->XMLTrans->CreditCardMonthLimit,
                'ACHPaymentPerTranLimit' => (string)$xmlAccount->XMLTrans->ACHPaymentPerTranLimit,
                'ACHPaymentMonthLimit' => (string)$xmlAccount->XMLTrans->ACHPaymentMonthLimit,
                'CreditCardMonthlyVolume' => (string)$xmlAccount->XMLTrans->CreditCardMonthlyVolume,
                'ACHPaymentMonthlyVolume' => (string)$xmlAccount->XMLTrans->ACHPaymentMonthlyVolume,
                'ReserveBalance' => (string)$xmlAccount->XMLTrans->ReserveBalance,
                'password' => $this->getPassword($this->_user_id)
            );
        } else {

            $data = array();
        }

        return array(
            'log_id' => $id,
            'status' => (string)$xmlResponse->XMLTrans->status,
            'message' => $this->getStatusMessage((string)$xmlResponse->XMLTrans->status),
            'data' => $data
        );
    }
    public function sendSignup() {

        self::$xml = new \SimpleXMLElement('<?xml version="1.0"?><!DOCTYPE Request.dtd><XMLRequest></XMLRequest>');
        self::$xml->addChild('certStr', $this->getSignupCert());
        self::$xml->addChild('termid', $this->getSignupTermId());
        self::$xml->addChild('class', $this->getClassType());

        $xmlTrans = self::$xml->addChild('XMLTrans');
        $xmlTrans->addChild('transType', $this->_transType);
        $xmlTrans->addChild('sourceEmail', $this->_sourceEmail);
        $xmlTrans->addChild('firstName', $this->_firstName);
        $xmlTrans->addChild('lastName', $this->_lastName);
        $xmlTrans->addChild('addr', $this->_addr);
        $xmlTrans->addChild('aptNum', $this->_aptNum);
        $xmlTrans->addChild('city', $this->_city);
        $xmlTrans->addChild('state', $this->_state);
        $xmlTrans->addChild('zip', $this->_zip);
        $xmlTrans->addChild('country', $this->_country);
        $xmlTrans->addChild('dayPhone', $this->_dayPhone);
        $xmlTrans->addChild('evenPhone', $this->_evenPhone);
        $xmlTrans->addChild('externalId', $this->_externalId);
        $xmlTrans->addChild('ssn', $this->_ssn);
        $xmlTrans->addChild('dob', $this->_dob);
        $xmlTrans->addChild('tier', $this->_tier);
        $xmlRequest = self::$xml->asXML();

        $response = $this->sendRequest($xmlRequest);
        $xmlResponse = simplexml_load_string($response);

        $this->_xml_sent = (string)$xmlRequest;
        $this->_xml_return = (string)$response;

        /**
         * Log the signup to the database table propay_signup_log.
         */
        $id = $this->log(array(
            'user_id' => $this->_user_id,
            'xml_sent' => $this->_xml_sent,
            'xml_return' => $this->_xml_return
        ));

        /**
         * Save the return data.
         */
        if ((string)$xmlResponse->XMLTrans->status === '00') {

            $this->saveAccnt(array(
                'user_id' => $this->_user_id,
                'accntNum' => (string)$xmlResponse->XMLTrans->accntNum,
                'xml_string' => $this->_xml_return
            ));
        } elseif  ((string)$xmlResponse->XMLTrans->status === '99') {

            $this->saveAccnt(array(
                'user_id' => $this->_user_id,
                'accntNum' => (string)$xmlResponse->XMLTrans->accntNum,
                'xml_string' => $this->_xml_return
            ));
        }

        return array(
            'log_id'  => $id,
            'status'  => (string)$xmlResponse->XMLTrans->status,
            'message' => $this->getStatusMessage((string)$xmlResponse->XMLTrans->status)
        );
    }
    private function sendRequest($xmlRequest) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->getPropayApiUrl(),
            CURLOPT_PORT, 8080,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $xmlRequest,
            CURLOPT_TIMEOUT => 30
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
    private function log($data) {

        $id = DB::table('propay_signup_log')->insertGetId(array(
            'user_id' => $data['user_id'],
            'xml_sent' => $data['xml_sent'],
            'xml_return' => $data['xml_return']
        ));

        return $id;
    }
    private function saveAccnt($data) {

        $accnt = DB::table('propay_accnt')
            ->where('user_id', '=', $data['user_id'])
            ->first();
        if ($accnt) {

            DB::table('propay_accnt')
                ->where('accnt_num', '=', $data['user_id'])
                ->update(array(
                    'accnt_num' => $data['accntNum'],
                    'xml_string' =>  $data['xml_string']
                ));
            return $data['user_id'];
        } else {

            DB::table('propay_accnt')
                ->insert(array(
                    'user_id' => $data['user_id'],
                    'accnt_num' => $data['accntNum'],
                    'xml_string' => $data['xml_string']
                ));
            return $data['user_id'];
        }
    }
    private function getPassword($user_id) {

        $result = DB::table('propay_accnt')
            ->where('user_id', '=', $user_id)
            ->first();
        if ($result) {

            $xmlAccount = simplexml_load_string($result['xml_string']);
            if ($xmlAccount) {

                return (string)$xmlAccount->XMLTrans->password;
            } else {

                return '';
            }
        } else {

            return '';
        }
    }
}