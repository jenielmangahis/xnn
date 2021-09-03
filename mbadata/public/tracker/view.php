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
	
	// New query : faster
	$sq = "SELECT userid, url, ip,count(ip) as ipcount, DATE(visitdate) as visitdate from traffic where userid=:userid and DATE_FORMAT(visitdate,'%Y-%m-%d') BETWEEN :visitdatefrom and :visitdateto GROUP BY `ip`,`url`";
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

} else{
	//$sq = "SELECT DISTINCT userid,url,ip, count(ip) as ipcount,DATE(visitdate) as visitdate from traffic  GROUP BY `ip`,`url` HAVING userid=:userid and visitdate BETWEEN '".date("Y-m-d")."' and '".date("Y-m-d")."'";
	$sq = "SELECT userid, ip,count(ip) as ipcount,userid,url, DATE(visitdate) as visitdate from traffic where userid=:userid and DATE_FORMAT(visitdate,'%Y-%m-%d') BETWEEN '".date("Y-m-d")."' and '".date("Y-m-d")."' GROUP BY `ip`,`url`";
	$stmt = $dbh->prepare($sq);
}

if(isset($_GET['userid'])){
	$usr = $_GET['userid'];
}else{
	$usr = $_POST['userid'];
}

$stmt->bindParam(':userid',$usr, PDO::PARAM_STR); 
$stmt->execute();
$results=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totpages=0;
$uniqueviews=array();
$urlz=array();
$totpageviews=0;
    /*** loop over the object directly ***/
    foreach($results as $key=>$val)
    {
		
		foreach($val as $dataz=>$val){
			if($dataz=='ipcount'){
				$totpageviews=$totpageviews+$val;
			}
			if($dataz=='ip'){
				$uniqueviews[$val]=$val;
			}if($dataz=='url'){
				$urlz[$val]=$val;
			}
		}
    }
$uniques = count($uniqueviews);
$totpages = count($urlz);
//print_r($uniqueviews);
$json=json_encode($results);
$data = json_decode($json, true);

$data['pagedetail']= array(
				'totpages'=>$totpageviews, 
				'uniqueusers'=>$uniques,
				'totalpageviews'=>$totpages
				);
				
$json = json_encode($data);
$dbh = null;

echo $json;
    }
catch(PDOException $e)
    {
    echo json_encode($e->getMessage());
}
?>