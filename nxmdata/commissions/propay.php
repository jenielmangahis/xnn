<?php
require_once 'edw.commission.php';

define('CERT_STR_TEST', '110324312106335764'); // This cert string is confidential
define('CERT_STR', '110324312106335764');
define('TRANS_TYPE', 2);  // This is always set to 2
define('CLASS_TYPE', 'partner');

class Propay {
    static $xml;
    private $test;
    private $proPayApiUrl;
    
    function __construct($test = false) {
        
        $this->test = $test;
        if ($this->test) {
            $this->proPayApiUrl = "http://sandbox.ko-kard.com/Gateway/?";
        } else {
            $this->proPayApiUrl = "https://sandbox.ko-kard.com/Gateway/?";
        }
    } /* __construct($test = false) */
    
    public function payCommissions($account_number = null, $amount = null, $invoice_number = null, $comment1 = null, $comment2 = null) {
        $retval = false;
        $amount = round($amount, 2);
        $amount = ($amount * 100);
        if ($this->test) {
            $cert_str = CERT_STR_TEST;
        } else {
            $cert_str =  CERT_STR;
        } /* ($this->test) */

        self::$xml = new SimpleXMLElement("<XMLRequest></XMLRequest>");
        self::$xml->addChild('certStr', $cert_str);
        self::$xml->addChild('class', CLASS_TYPE);

        $xmlTrans = self::$xml->addChild('XMLTrans');
        $xmlTrans->addChild('transType', TRANS_TYPE);
        $xmlTrans->addChild('amount', $amount);
        $xmlTrans->addChild('recAccntNum', $account_number);

        // Optional Fields
        if ($invoice_number) {
            $xmlTrans->addChild('invNum', $invoice_number);
        };

        if ($comment1) {
            $xmlTrans->addChild('comment1', $comment1);
        };

        if ($comment2) {
            $xmlTrans->addChild('comment2', $comment2);
        };

        $requestXml = self::$xml->asXML();
        $retval = $this->sendRequest($requestXml);

        // $xmlResponse = simplexml_load_string($response);

        $edw = new EDW_Commission();
        $edw->logPropay(
                 $invoice_number
                ,$account_number
                ,$amount
                ,$requestXml
                ,$retval
            );
        
        return $retval;
    } /* payCommissions */

    public function checkBalance() {

    } /* checkBalance */

    private function sendRequest($requestXml) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->proPayApiUrl,
            CURLOPT_PORT, 8080,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $requestXml,
            CURLOPT_TIMEOUT => 30
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    } /* sendRequest */

    private function getStatusMessage($code) {
        $message = '';
        return $message;
    }
}

?>