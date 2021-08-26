<?php
	require_once('payap_api.php');
	
	// Create User.
	$params = array();
	$params['phone_number'] = "+639463998006";
	$params['first_name'] = "Levi";
	$params['middle_name'] = "";
	$params['last_name'] = "Logics";
	$params['address'] = "123 Main St.";
	$params['city'] = "Big City";
	$params['state'] = "";
	$params['zip_code'] = "8000";
	$params['country'] = "PH";
	$params['profile_image'] = "";
	$params['cover_img'] = "";
	$params['gender'] = "M";
	$params['dob'] = "7/10/1983";
	$params['user_tag'] = "";
	$params['reference'] = 00;
	$response = send_request(array("users"), "POST", $params);
	var_dump($response);
?>