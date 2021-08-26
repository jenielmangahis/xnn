<?php

	require_once('api_wrapper.php');

	$params["hashKey"] = '5729f6b6-dd80-4f39-8d4f-786b48f58ca7';
	$params["sitename"] = 'paulaIMLsupport';
	$params["password"] = 'BOKTPpcx';
	$result = send_request("users", "GET", $params);

	echo $result;
?>