<?php 
date_default_timezone_set('UTC');
//TRACKING View FOR GTT Created by : solomon@naxum.com
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

try {
$dbh = new PDO("mysql:host=dbserver;dbname=mba",'apache','fr1ckl3');

if(isset($_POST['datespecific'])){
	$posspec = $_POST['datespecific'];
}else{
	$posspec = $_GET['datespecific'];
}

if($posspec==1){
	//$sq = "SELECT DISTINCT userid,url,ip, count(ip) as ipcount,DATE(visitdate) as visitdate from traffic  GROUP BY `ip`,`url` HAVING userid=:userid and visitdate BETWEEN(:visitdatefrom and :visitdateto)";
	
	//$sq = "SELECT DISTINCT userid,url,ip, count(ip) as ipcount,DATE(visitdate) as visitdate from traffic  GROUP BY `ip`,`url` HAVING userid=:userid and visitdate BETWEEN :visitdatefrom and :visitdateto";
	//New query : faster
	$sq = "SELECT userid, url, ip,count(ip) as ipcount, DATE(visitdate) as visitdate from traffic where userid=:userid and visitdate BETWEEN :visitdatefrom and :visitdateto GROUP BY `ip`,`url`";
	$stmt=$dbh->prepare($sq);	

	if(isset($_POST['visitdatefrom'])){
		$vistdatefrom = $_POST['visitdatefrom'];
	}else{
		$vistdatefrom = $_GET['visitdatefrom'];
	}

	if($vistdatefrom!=''){		
		$time = strtotime($vistdatefrom);
		$visitdate = date('Y-m-d',$time);
		$stmt->bindParam(':visitdatefrom', $visitdate, PDO::PARAM_STR); 
	}else{
		$visitdate = date("Y-m-d");
		$stmt->bindParam(':visitdatefrom', $visitdate, PDO::PARAM_STR); 
	}

	if(isset($_POST['visitdateto'])){
		$vistdateto = $_POST['visitdateto'];
	}else{
		$vistdateto = $_GET['visitdateto'];
	}

	if($vistdateto!=''){		
		$time2 = strtotime($vistdateto);
		$visitdateto = date('Y-m-d',$time2);
		$stmt->bindParam(':visitdateto', $visitdateto, PDO::PARAM_STR); 
	}else{
		$visitdateto = date("Y-m-d");
		$stmt->bindParam(':visitdateto', $visitdateto, PDO::PARAM_STR); 
	}
}
else{
	//$sq = "SELECT DISTINCT userid,url,ip, count(ip) as ipcount,DATE(visitdate) as visitdate from traffic  GROUP BY `ip`,`url` HAVING userid=:userid and visitdate BETWEEN '".date("Y-m-d")."' and '".date("Y-m-d")."'";
	$sq = "SELECT userid, ip,count(ip) as ipcount,url, DATE(visitdate) as visitdate from traffic where userid=:userid and visitdate BETWEEN '".date("Y-m-d")."' and '".date("Y-m-d")."' GROUP BY `ip`,`url`";
	$stmt = $dbh->prepare($sq);
}

if(isset($_GET['userid'])){
	$usr = $_GET['userid'];
}else{
	$usr = $_POST['userid'];
}

$stmt->bindParam(':userid',$usr, PDO::PARAM_STR); 
$stmt->execute();
$f = $stmt->fetchAll();
$x=0;
$urlz = array();
$uri= array();
foreach ($f as $row)
{
	$urlz[$row['url']]['visitcount'][$x]= $row['ipcount'];
	$x++;
}

$x =0;
if(is_array($urlz)){
foreach($urlz as $key=>$value){
	$x++;
	$totskie=0;
	foreach($value as $vis){
		foreach($vis as $counts){
			$totskie = $totskie+ $counts;
		}
	}
	#$uri[$x][$key] = $totskie;
	$uri[$x]['url'] = $key;
	$uri[$x]['visits'] = $totskie;
	#$url[$x]['views'] = $totskie;
	
	
}
}


$json = json_encode($uri);



//print_r($urlz);


$dbh = null;

echo $json;
    }
catch(PDOException $e)
    {
    echo json_encode($e->getMessage());
}


?>