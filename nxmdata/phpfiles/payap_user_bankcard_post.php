<?php 
	include_once('payap_api.php');

	$params = array();
	$params['cardName'] = "John Doe";
	$params['cardNumber'] = "4111111111";
	$params['cardExpiration'] = "12/2018";
	$params['cardCVV'] = "123";
	$response = send_request(array("users", 1, "BankCards"), "POST", $params);
	var_dump($response);
?>