<?php 
	require_once('payap_api.php');

	// Create User.
	$params = array();
	$params['phone_number'] = "18885551212";
	$params['first_name'] = "Joseph";
	$params['middle_name'] = "A";
	$params['last_name'] = "Banks";
	$params['address'] = "123 Main St.";
	$params['city'] = "Big City";
	$params['state'] = "CA";
	$params['zip_code'] = "90210";
	$params['country'] = "US";
	$params['profile_image'] = "";
	$params['cover_img'] = "";
	$params['gender'] = "M";
	$params['dob'] = "1/23/1945";
	$params['user_tag'] = "";
	$params['reference'] = 100230400;
	$response = send_request(array("users"), "POST", $params);
	var_dump($response);
	
	// Get User.
	$uid = 1;
	$response = send_request(array("users",$uid), "GET", null);
	var_dump($response);
	
	// Update User.
	$uid = 1;
	$params = array();
	$params['phone_number'] = "18885551212";
	$params['first_name'] = "Joseph";
	$params['middle_name'] = "A";
	$params['address'] = "123 Main St.";
	$params['city'] = "Big City";
	$params['state'] = "CA";
	$params['zip_code'] = "90210";
	$params['country'] = "US";
	$params['gender'] = "M";
	$response = send_request(array("users", $uid), "PUT", $params);
	var_dump($response);


	// Delete User.
	$uid = 1;
	$response = send_request(array("users",$uid), "DELETE", null);
	var_dump($response);
?>