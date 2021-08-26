<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/db.config.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/DB.class.new.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/commissions/commission.config.php');

class Payap {

	protected $db;
	protected $m_userId;
	protected $m_publicKey = "fFOuxnprKrNZxAIedMe7c21iPwPOVX91";
	protected $m_privateKey = "cNlP1Q7B7KbvLkQo";
	protected $m_serviceUrl = "https://api.payap.co/";

	
	public function __construct($userId = 0){	
		
		$this->m_userId = $userId;
		$this->db = Database::getInstance()->getDB();			
	}

	/**
	 * Get user information from the payap.
	 * This information is keep by payap and we will gonna get this via user id from payap.
	 */
	public function getUser() {
		
		$response = $this->sendRequest(
						 array("users", $this->m_userId) 	/* $resources */
						,"GET"								/* $method */
						,null 								/* $params */
					);
		return $response;
	}

	/**
	 * We have a table of users with payap account.
	 * Now this function return a json format of the user by $userId.
	 */
	public function getSendMoneyParam($userId) {
		
	    $sql = "SELECT
	    			 cm_payap.user_id
	    			,cm_payap.cell_number
	    		FROM cm_payap
	    		WHERE cm_payap.user_id = :user_id;";

	    $stmt = $this->db->prepare($sql);
	    $stmt->bindParam(':user_id', $userId);
	    $stmt->execute();
	    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	    
	    return json_encode($result);		
	}

	/**
     * 
	 */
	public function addBankCard($Cardname, $CardNumber, $CardExpiration, $CardCvv) {

		$params = array();
		$params['cardName'] = $Cardname;
		$params['cardNumber'] = $CardNumber;
		$params['cardExpiration'] = $CardExpiration;
		$params['cardCVV'] = $CardCvv;
		$response = $this->sendRequest(array("users", $this->m_userId, "BankCards"), "POST", $params);
		
		return $response;
	}

	public function getCurrencyCodes() {

	    $sql = "SELECT 
	    			* 
	    		FROM cm_ewallet_currency_codes 
	    		ORDER BY cm_ewallet_currency_codes.`code` 
	    		ASC;";
	    $stmt = $this->db->prepare($sql);
	    $stmt->execute();
	    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	    
	    return json_encode($result);		
	}

	/**
	 * 
	 */
	public function sendMoney(
						$cid, 			// Card ID
						$toType,   		// business or personal
						$toPhone,  		// Receiver cell no.
						$amount, 		// Amount sent.
						$memo) { 		// Message of the sender.

		$params = array();
		$params['cid'] = $cid;
		$params['to_type'] = $toType;
		$params['toPhone'] = $toPhone;
		$params['amount'] = $amount;
		$params['memo'] = $memo;
		$response = $this->sendRequest(array("users", $this->m_userId, "Accounts", "USD", "SendMoney"), "POST", $params);
		
		return $response;
	}

	private function sendRequest($resources, $method, $params)
	{
		$res1 = array_shift($resources);
		$res1id = array_shift($resources);
		$res2 = array_shift($resources);
		$res2id = array_shift($resources);
		$res3 = array_shift($resources);
		$serviceUrl = $this->m_serviceUrl;
		
		if (!empty($res1)) {
			$serviceUrl .= $res1;
		}
		
		if (!empty($res1id)) {
			$serviceUrl .= "/$res1id";
		}
		
		if (!empty($res2)) {
			$serviceUrl .= "/$res2";
		}
		
		if (!empty($res2id)) {
			$serviceUrl .= "/$res2id";
		}
		
		if (!empty($res3)) {
			$serviceUrl .= "/$res3";
		}
		
		if ($params == null) {
			$params = array();
		}
		
		if (!is_array($params)) {
			return false;
		}
		
		$secretKey = $this->m_privateKey;
		$bucket = "$res1";
		$item = "$res1id";
		$timestamp = time();
		$strtosign = "$method\n\n\n$timestamp\n/$bucket/$item";
		$signature = urlencode(base64_encode(hash_hmac("sha1", utf8_encode($strtosign), $secretKey, true)));
		
		$username = $this->m_publicKey;
		$password = $signature;
		
		$request_params = array();
		$request_params['api_public_key'] = $this->m_publicKey;
		$request_params['api_timestamp'] = $timestamp;
		$request_params['params'] = $params;
		
		if ($method == 'GET') {
			$serviceUrl .= '?' . http_build_query($request_params);
		}
		
		$ch = curl_init($serviceUrl);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
		
		if ($method == 'POST') {
			
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_params));
		} elseif ($method == 'PUT') {
			
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_params));
		} elseif ($method == 'DELETE') {
			
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_params));
		} /* ($method == 'POST') */
		
		$curl_response = curl_exec($ch);
		curl_close($ch);
		
		if ($curl_response === false) {

			return false;
		}
		
		return $curl_response;
	} // send_request

} // class

?>