<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/includes/db.config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/includes/DB.class.new.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/commissions/commission.config.php");

class kokard {
	protected $db;
	protected $affiliate_id;
	protected $affiliate_key;
	protected $api_id;
	protected $url;
	
	/**
	* Constructor.
	* $mode = Two possible values "Live", "Test".
	*/
	public function __construct($mode = "Live"){	
		
		$this->db = Database::getInstance()->getDB();
		$this->affiliate_id = "";
		$this->affiliate_key = "";
		$this->api_id = "";
		
		if ($mode == "Live") {
			$this->url = "https://www.ko-kard.com/Gateway/?";	
		} else {
			$this->url = "http://sandbox.ko-kard.com/Gateway/?";
		}
	}
	
	/**
	* Register a sender.
	*/
	function registerSender(
				 $first_name
				,$last_name
				,$middle_initial
				,$birth_date
				,$email
				,$area_code
				,$phone_number
				,$country
				,$state
				,$city
				,$address_line_1
				,$address_line_2
				,$zip_code
				,$affiliate_client_id) {
		
		$fields = "affiliate_id=" . $this->affiliate_id . "&" 
				. "affiliate_key=" . $this->affiliate_key . "&" 
				. "api_id=" . $this->api_id . "&"
				. "first_name=" . $first_name . "&"
				. "last_name=" . $last_name . "&"
				. "birth_date=" . $birth_date . "&"
				. "email=" . $email . "&"
				. "area_code=" . $area_code . "&"
				. "phone_number=" . $phone_number . "&"
				. "country=" . $country . "&"
				. "state=" . $state . "&"
				. "city=" . $city . "&"
				. "address_line_1=" . $address_line_1 . "&"
				. "address_line_2=" . $address_line_2 . "&"
				. "zip_code=" . $zip_code . "&"
				. "affiliate_client_id" . $affiliate_client_id . "";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://sandbox.ko-kard.com/Gateway/?");
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		
		echo json_encode($server_output);
	} /* registerSender */
}

?>