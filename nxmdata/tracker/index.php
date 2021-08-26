<?php 
//TRACKING CODE FOR GTT Created by : solomon@naxum.com
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");                           
try {
$dbh = new PDO("mysql:host=dbserver;dbname=nxm",'apache','fr1ckl3');
$sql = "INSERT INTO `traffic` (`url`, `userid`, `visitdate`, `ip`) VALUES (:url, :userid,:visitdate, :ip)";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':url', $_POST['url'], PDO::PARAM_STR);       
$stmt->bindParam(':userid', $_POST['userid'], PDO::PARAM_STR);       
$stmt->bindParam(':visitdate', date("Y-m-d H:i:s"), PDO::PARAM_STR);       
$stmt->bindParam(':ip', $_POST['ip'], PDO::PARAM_STR);       
$stmt->execute(); 
$dbh = null;
echo '{"response":"ok"}';
    }
catch(PDOException $e)
    {
    echo $e->getMessage();
}
?>