<?php
header('Access-Control-Allow-Origin: *'); //some systems dont hae allow originss..

$system = 'gtt'; //change these for other systems


include('includes/function.php');
include('includes/connection.php');
connection_open();
if($_GET['method'] == 'updateData'){

/*
$data_array = array(
	$_GET['catid'] => $_GET['id']
);
$table_name = "sm_medias";
json_encode(update($data_array, $table_name));
connection_close();*/








	try {
			$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
			$catid = $dbh->quote($_GET['catid']);
$xid = $dbh->quote($_GET['id']);
$titlemedia = $dbh->quote($_GET['titlemedia']);
$contentmedia = $dbh->quote($_GET['contentmedia']);
$urlmedia = $dbh->quote($_GET['urlmedia']);
			$dbh->exec("UPDATE `sm_medias` SET `catid`=$catid, `content`=$contentmedia, `title`=$titlemedia, `url`=$urlmedia WHERE (`id`=$xid)");
			$res['response'] = 'success';
			echo json_encode($res);
		}
			catch(PDOException $e)
		{
			echo $e->getMessage();
		}

}

if($_GET['method'] == 'updateDataVideo'){


$data_array = array(
	$_GET['catid'] => $_GET['vidname']
);
$table_name = "sm_medias";
json_encode(updateVideo($data_array, $table_name));
connection_close();
}




if($obj->method == 'getCats'){

$a = array();
try {
    $dbh = new PDO("mysql:host=".SERVER_NAME.";dbname=$system", SERVER_USERNAME, SERVER_PASSWORD);
    $sql = "SELECT * FROM `sm_categories`";
	$stmt=$dbh->prepare($sql);
$stmt->execute();
$results=$stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);


    $dbh = null;
	
	
	
	
}
catch(PDOException $e)
    {
		echo $e->getMessage();
    }


connection_close();	

}
 
if($_GET['method'] == 'updateDataEdit'){
try {
		$dbh = new PDO("mysql:host=".SERVER_NAME.";dbname=$system", SERVER_USERNAME, SERVER_PASSWORD);
		
		$content = $dbh->quote($_GET['content']);
		$title = $dbh->quote($_GET['title']);
		$url = $dbh->quote($_GET['url']);
		$sql = "UPDATE `sm_medias` SET `catid`='".$_GET['catid']."', `content`=$content, `title`=$title, `url`=$url WHERE (`id`='".$_GET['id']."')";
		
		$stmt=$dbh->prepare($sql);
		$stmt->execute();
		$res['response'] = 'success';
			echo json_encode($res);
			
	}
catch(PDOException $e)
    {
		echo $e->getMessage();
    }
connection_close();	
}



if($_GET['method']=='delCat'){
		try {
			$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
			$xid = $dbh->quote($_GET['xid']);
			$dbh->exec("DELETE FROM `sm_categories` WHERE `id`=$xid");
			$res['response'] = 'success';
			echo json_encode($res);
		}
			catch(PDOException $e)
		{
			echo $e->getMessage();
		}

}



if($_GET['method'] == 'getData'){

$a = array();
try {
    $dbh = new PDO("mysql:host=".SERVER_NAME.";dbname=$system", SERVER_USERNAME, SERVER_PASSWORD);
    $sql = "SELECT * FROM `sm_medias` where id=".$_GET['id'];
	$stmt=$dbh->prepare($sql);
	$stmt->execute();
	$results=$stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($results);
	$dbh = null;

	}
catch(PDOException $e)
    {
		echo $e->getMessage();
    }


connection_close();	

}


if($_GET['method'] == 'getAllAssets'){
try {
    $dbh = new PDO("mysql:host=".SERVER_NAME.";dbname=$system", SERVER_USERNAME, SERVER_PASSWORD);
    $sql = "SELECT * FROM `sm_medias` where catid=".$_GET['xid'];
	$stmt=$dbh->prepare($sql);
	$stmt->execute();
	$rws = $stmt->rowCount();
	echo $rws;
	$dbh = null;
	}
catch(PDOException $e)
    {
		echo $e->getMessage();
    }
connection_close();	
}




?>