<?php
#header("Access-Control-Allow-Origin: *"); //some systems dont hae allow originss..
#header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN'] . "");

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

}



$system = 'gtt'; //change these for other systems


if($_POST['method']=='addCat'){
		try {
			$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
			$name = $dbh->quote($_POST['name']);
			$dbh->exec("INSERT INTO sm_categories(name,userid,share) VALUES (".$name.", '1','1')");
			$res['response'] = 'success';
		
			echo json_encode($res);
		}
			catch(PDOException $e)
		{
			echo $e->getMessage();
		}

}


if($_GET['method']=='getImages'){
	
try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
    /*** echo a message saying we have connected ***/

    /*** The SQL SELECT statement ***/
    $sql = "SELECT
sm_medias.id,
sm_medias.media,
sm_medias.type,
sm_medias.catid ,
sm_medias.content ,
sm_medias.title ,
sm_categories.id AS catidf,
sm_categories.`name`
FROM
sm_medias
INNER JOIN sm_categories ON sm_medias.catid = sm_categories.id where sm_medias.type=0 
";

if(isset($_GET['cat'])){
$sql = "SELECT
sm_medias.id,
sm_medias.media,
sm_medias.type,
sm_medias.catid,
sm_medias.content,
sm_medias.title,
sm_categories.id AS catidf,
sm_categories.`name`
FROM
sm_medias
INNER JOIN sm_categories ON sm_medias.catid = sm_categories.id where sm_medias.type=0 and sm_categories.id=
".$_GET['cat'];
}

// /var/rep/files/globaltraffictakeover.com/www/images/social #UPLOAD LOC
  $x=0;
  $v = array();
foreach ($dbh->query($sql) as $row){
$v[$x]['id'] =$row['id'];
$v[$x]['media']=$row['media'];
$v[$x]['type']=$row['type'];
$v[$x]['catid']=$row['catid'];
$v[$x]['content']=UpdateMergeCode($row['id'],$system);
$v[$x]['title']=$row['title'];
$v[$x]['name']=$row['name'];
$x++;
}

// $stmt=$dbh->prepare($sql);
// $stmt->execute();
// $results=$stmt->fetchAll(PDO::FETCH_ASSOC);
// echo json_encode($results);
 echo json_encode($v);



$dbh = null;
}
catch(PDOException $e)
{
echo $e->getMessage();
}
	
}



function UpdateMergeCode($id,$system){
	$datax ='';
try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
$sql = "SELECT * from `sm_medias` where id=".$id;

foreach ($dbh->query($sql) as $row){
	
	

$datax =  str_replace("#URL#", $row['url'],$row['content']);
$datax =  str_replace("#url#", $row['url'],$datax);

}
	$dbh = null;
	}
		catch(PDOException $e)
	{
		echo $e->getMessage();
	}

	
return $datax;	
}


if($_GET['method']=='myFavorites'){
	
try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
    /*** echo a message saying we have connected ***/
$xf = $dbh->quote($_GET['userid']);
    /*** The SQL SELECT statement ***/
$sql = "SELECT
sm_favorites.id,
sm_favorites.userid,
sm_favorites.media_id,
sm_medias.media,
sm_medias.catid,
sm_medias.type
FROM
sm_medias
INNER JOIN sm_favorites ON sm_favorites.media_id = sm_medias.id where userid=$xf";



// /var/rep/files/globaltraffictakeover.com/www/images/social #UPLOAD LOC
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
	
}



if($_GET['method']=='myFavoritesAdmin'){
	
try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');

$sql = "SELECT
sm_medias.id,
sm_medias.media,
sm_medias.type,
sm_medias.catid,
sm_medias.content,
sm_medias.title,
sm_medias.url,
sm_medias.adminfeatured,
sm_categories.`name`
FROM
sm_medias
LEFT JOIN sm_categories ON sm_medias.catid = sm_categories.id
";



// /var/rep/files/globaltraffictakeover.com/www/images/social #UPLOAD LOC
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
	
}



if($_GET['method']=='getCatDisplay'){
	try {
		$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
		$sql = "select * from sm_categories WHERE (`id`='".$_GET['catid']."')";
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
}



if($_GET['method']=='adminfeatured'){
	try {
		$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
		$sql = "UPDATE `sm_medias` SET `adminfeatured`='1' WHERE (`id`='".$_GET['mediaid']."')";
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
}
if($_GET['method']=='adminunfeatured'){
	try {
		$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
		$sql = "UPDATE `sm_medias` SET `adminfeatured`='0' WHERE (`id`='".$_GET['mediaid']."')";
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
}

if($_GET['method']=='getVideos'){
	
try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
    /*** echo a message saying we have connected ***/

    /*** The SQL SELECT statement ***/
    $sql = "SELECT
sm_medias.id,
sm_medias.media,
sm_medias.type,
sm_medias.catid,
sm_categories.id AS catidf,
sm_categories.`name`
FROM
sm_medias
INNER JOIN sm_categories ON sm_medias.catid = sm_categories.id where sm_medias.type=1 
";

if(isset($_GET['cat'])){
$sql = "SELECT
sm_medias.id,
sm_medias.media,
sm_medias.type,
sm_medias.catid,
sm_categories.id AS catidf,
sm_categories.`name`
FROM
sm_medias
INNER JOIN sm_categories ON sm_medias.catid = sm_categories.id where sm_medias.type=1 and sm_categories.id=
".$_GET['cat'];
}

// /var/rep/files/globaltraffictakeover.com/www/images/social #UPLOAD LOC
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
	
}


if($_GET['method']=='sharedata'){
	try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
    /*** echo a message saying we have connected ***/

    /*** The SQL SELECT statement ***/
    $sql = "SELECT * FROM sm_medias where id=".$_GET['id'];
// /var/rep/files/globaltraffictakeover.com/www/images/social #UPLOAD LOC
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


}


if($_GET['method']=='removeFav'){
	try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
$us = $dbh->quote($_GET['userid']);
$md = $dbh->quote($_GET['media']);
$sql = "DELETE FROM `sm_favorites` WHERE (`userid`=$us,`media_id`=$md)";
$stmt=$dbh->prepare($sql);
$stmt->execute();
}
catch(PDOException $e)
{
	echo $e->getMessage();
}
echo '{"response":"success"}';
}

if($_GET['method']=='addFav'){
		$res = array();
		try {
			$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
			$us = $dbh->quote($_GET['userid']);
			$md = $dbh->quote($_GET['media']);
			$sql = "SELECT * FROM sm_favorites where media_id=$md and userid=$us";
			$stmt=$dbh->prepare($sql);
			$stmt->execute();
			$r = $stmt->fetchColumn(); 
			if($r==0){
				$dbh->exec("INSERT INTO sm_favorites(userid, media_id) VALUES ($us, $md)");
				$res['response'] = 'success';
		}else{
			$res['response'] = 'error';
		}
			echo json_encode($res);
		}
			catch(PDOException $e)
		{
			echo $e->getMessage();
		}
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



if($_GET['method']=='delMedia'){
		try {
			$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
			$xid = $dbh->quote($_GET['xid']);
			$dbh->exec("DELETE FROM `sm_medias` WHERE `id`=$xid");
			$res['response'] = 'success';
			echo json_encode($res);
		}
			catch(PDOException $e)
		{
			echo $e->getMessage();
		}

}


if($_GET['method']=='updateDataVideo'){
		try {
			$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
			$catid = $dbh->quote($_GET['catid']);
			$contents = $dbh->quote($_GET['contents']);
			$title = $dbh->quote($_GET['titles']);
			$urls = $dbh->quote($_GET['urls']);
			$media = $dbh->quote($_GET['vidname']);


			$dbh->exec("UPDATE sm_media SET catid=$catid, content=$contents, title=$title, url=$urls where media=$media");
			$dbh->exec("UPDATE `sm_medias` SET `catid`=$catid, `content`=$contents, `title`=$title, `url`=$urls WHERE (`media`=$media)");
			$res['response'] = 'success';
			echo json_encode($res);
		}
			catch(PDOException $e)
		{
			echo $e->getMessage();
		}

}



if($_GET['method']=='categs'){
	
try {
$dbh = new PDO("mysql:host=dbserver;dbname=$system",'apache', 'fr1ckl3');
    /*** echo a message saying we have connected ***/

    /*** The SQL SELECT statement ***/
    $sql = "SELECT * FROM sm_categories";
// /var/rep/files/globaltraffictakeover.com/www/images/social #UPLOAD LOC
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
	
}

?>