<?php 
	require_once('payap_api.php');

	// Delete User.
	// $uid = 1;
	// $response = send_request(array("users",$uid), "DELETE", null);
	// var_dump($response);

	// 
	$params = array();
	// $params['action'] = "";
	// $params['uid'] = "1234";
	$params['cardName'] = "John Doe";
	$params['cardNumber'] = "4111111111";
	$params['cardExpiration'] = "12/2018";
	$params['cardCVV'] = "123";
	$response = send_request(array("users", 1, "BankCards"), "POST", $params);
	var_dump($response);
?>