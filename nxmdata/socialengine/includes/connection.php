<?php
// Connection Start Server
function connection_open(){
define('SERVER_NAME', 'dbserver');
define('SERVER_USERNAME', 'apache');
define('SERVER_PASSWORD', 'fr1ckl3');
global $connection;
$connection = mysql_connect(SERVER_NAME, SERVER_USERNAME,SERVER_PASSWORD);
if(!$connection){
die('Server connection not establish' . mysql_error());
}
$db = mysql_select_db('gtt', $connection);
if(!$db){
die('Database connection Failed.' . mysql_error());
}
}
// Connection Close 
function connection_close(){
global $connection;
if($connection){
mysql_close($connection);
}
}
?>