<?php

namespace App\nxm\models;
use \Illuminate\Database\Capsule\Manager as DB;

class PropayPayment extends PropayAccount
{
    static $xml;
    private $_user_id;
    private $_xml_sent;
    private $_xml_return;
    private $_amount;
    private $_recAccntNum;
    private $_invNum;
    private $_comment1;
    private $_comment2;

    public function setUserId($user_id) {
        $this->_user_id = $user_id;
        return $this;
    }
    public function setAmount($amount) {
        $this->_amount = $amount;
        return $this;
    }
    public function setRecAccntNum($recAccntNum) {
        $this->_recAccntNum = $recAccntNum;
        return $this;
    }
    public function setInvNum($invNum) {
        $this->_invNum = $invNum;
        return $this;
    }
    public function setComment1($comment1) {
        $this->_comment1 = $comment1;
        return $this;
    }
    public function setComment2($comment2) {
        $this->_comment2 = $comment2;
        return $this;
    }
    public function __construct($test = false) {

        parent::__construct($test);
        $this->_invNum = '';
        $this->_recAccntNum = '';
        $this->_amount = 0.00;
        $this->_comment1 = '';
        $this->_comment2 = '';
    }
    public function send() {
        $this->_amount = round($this->_amount, 2);
        $this->_amount *= 100;

        self::$xml = new \SimpleXMLElement('<?xml version="1.0"?><!DOCTYPE Request.dtd><XMLRequest></XMLRequest>');
        self::$xml->addChild('certStr', $this->getDisbursementCert());
        self::$xml->addChild('termid', $this->getDisbursementTermId());
        self::$xml->addChild('class', $this->getClassType());
        $xmlTrans = self::$xml->addChild('XMLTrans');
        $xmlTrans->addChild('transType', 2);
        $xmlTrans->addChild('amount', $this->_amount);
        $xmlTrans->addChild('recAccntNum', $this->_recAccntNum);

        // Optional Fields.
        if ($this->_invNum) {
            $xmlTrans->addChild('invNum', $this->_invNum);
        }

        if ($this->_comment1) {
            $xmlTrans->addChild('comment1', $this->_comment1);
        }

        if ($this->_comment2) {
            $xmlTrans->addChild('comment2', $this->_comment2);
        }

        $requestXml = self::$xml->asXML();
        $response = $this->sendRequest($requestXml);
        $xmlResponse = simplexml_load_string($response);

        $this->_xml_sent = (string)$requestXml;
        $this->_xml_return = (string)$response;

        /**
         * Log the signup to the database table propay_signup_log.
         */
        $id = $this->log(array(
            'user_id' => $this->_user_id,
            'xml_sent' => $this->_xml_sent,
            'xml_return' => $this->_xml_return
        ));

        return array(
            'log_id'  => $id,
            'status'  => (string)$xmlResponse->XMLTrans->status,
            'message' => $this->getStatusMessage((string)$xmlResponse->XMLTrans->status)
        );
    }
    private function sendRequest($requestXml) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->getPropayApiUrl(),
            CURLOPT_PORT, 8080,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $requestXml,
            CURLOPT_TIMEOUT => 30
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
    private function log($data) {

        $id = DB::table('propay_payment_log')->insertGetId(array(
            'user_id' => $data['user_id'],
            'xml_sent' => $data['xml_sent'],
            'xml_return' => $data['xml_return']
        ));

        return $id;
    }
}