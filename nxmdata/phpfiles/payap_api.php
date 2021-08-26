<?php
define('PAYAP_API_PUBLIC_KEY','fFOuxnprKrNZxAIedMe7c21iPwPOVX91');
define('PAYAP_API_PRIVATE_KEY','cNlP1Q7B7KbvLkQo');

function send_request($resources, $method, $params)
{
	$service_url = "https://api.payap.co/";
	$res1 = array_shift($resources);
	$res1id = array_shift($resources);
	$res2 = array_shift($resources);
	$res2id = array_shift($resources);
	$res3 = array_shift($resources);
	
	if (!empty($res1)) {
		$service_url .= $res1;
	}
	
	if (!empty($res1id)) {
		$service_url .= "/$res1id";
	}
	
	if (!empty($res2)) {
		$service_url .= "/$res2";
	}
	
	if (!empty($res2id)) {
		$service_url .= "/$res2id";
	}
	
	if (!empty($res3)) {
		$service_url .= "/$res3";
	}
	
	if ($params == null) {
		$params = array();
	}
	
	if (!is_array($params)) {
		return false;
	}
	
	$secretKey = PAYAP_API_PRIVATE_KEY;
	$bucket = "$res1";
	$item = "$res1id";
	$timestamp = time();
	$strtosign = "$method\n\n\n$timestamp\n/$bucket/$item";
	$signature = urlencode(base64_encode(hash_hmac("sha1", utf8_encode($strtosign), $secretKey, true)));
	
	$username = PAYAP_API_PUBLIC_KEY;
	$password = $signature;
	
	$request_params = array();
	$request_params['api_public_key'] = PAYAP_API_PUBLIC_KEY;
	$request_params['api_timestamp'] = $timestamp;
	$request_params['params'] = $params;
	
	if ($method == 'GET') {
		$service_url .= '?' . http_build_query($request_params);
	}
	
	$ch = curl_init($service_url);
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
}

?>