<?php
header("Content-type: image/png");
$string =  $_SERVER["HTTP_REFERER"] . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'];
try {
	$dbh = new PDO("mysql:host=dbserver;dbname=nxm",'apache','fr1ckl3');
	$sql = "INSERT INTO `traffic` (`url`, `userid`, `visitdate`, `ip`,`browser`) VALUES (:url, :userid,:visitdate, :ip,:browser)";
	$stmt = $dbh->prepare($sql);
	$stmt->bindParam(':url', $_SERVER["HTTP_REFERER"], PDO::PARAM_STR);       
	$stmt->bindParam(':userid', $_GET['u'], PDO::PARAM_STR);       
	$stmt->bindParam(':visitdate', date("Y-m-d H:i:s"), PDO::PARAM_STR);       
	$stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);       
	$stmt->bindParam(':browser', $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);       
	$stmt->execute(); 
	$dbh = null;
		
}
catch(PDOException $e)
    {
    echo $e->getMessage();
}


$im     = imagecreatefrompng("button1.png");
$orange = imagecolorallocate($im, 220, 210, 60);
$px     = (imagesx($im) - 7.5 * strlen($string)) / 2;
imagestring($im, 3, $px, 9, $string, $orange);
imagepng($im);
imagedestroy($im);

?>