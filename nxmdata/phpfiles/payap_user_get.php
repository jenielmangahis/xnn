<?php 
	require_once('payap_api.php');

	// Get User.
	$uid = 1;
	$response = send_request(array("users", $uid), "GET", null);
	var_dump($response);
?>