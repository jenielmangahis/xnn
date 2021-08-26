<?php 
	include_once('payap_api.php');
	
	$params = array();
	$params['cid'] = "1";
	$params['to_type'] = "business";
	$params['toPhone'] = "+639463998006";
	$params['amount'] = "100.00";
	$params['memo'] = "pizza";
	$response = send_request(array("users", 1, "Accounts", "USD", "SendMoney"), "POST", $params);
	var_dump($response);
?>