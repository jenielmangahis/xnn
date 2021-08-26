<?php

function send_request($resources, $method, $params) {
	// Declaration section.
	//--------------------
	$service_url = "https://office.myimarketslive.co:81/";

	// Todo...
	// ---------
	if ($method == 'GET') {
		$service_url .= $resources . '?' . http_build_query($params);
	}
	
	$ch = curl_init($service_url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_response = curl_exec($ch);
	curl_close($ch);
	
	// Return...
	// ---------
	if ($curl_response === false) {
		return false;
	}
	
	return $curl_response;
}

?>