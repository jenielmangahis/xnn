<?php
//Simple IP getter CODE FOR GTT Created by : solomon@naxum.com
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
$ipresult['ip'] = $ip;
echo json_encode($ipresult);
?>