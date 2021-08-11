<?php
//include('wp-config.php');
include "../../../hostname.php";
header("Access-Control-Allow-Origin: *");
try {
    $dbh = new PDO("mysql:host=blog2020;dbname=xpirient", 'bloguser', 'bloguserpass');
    /*** echo a message saying we have connected ***/
    //echo 'Connected to database';
	// old host 206.251.247.133
	
	$sql = "select * from wp_xpirient_posts where `post_status`='publish'";
    foreach ($dbh->query($sql) as $row) {
		//$sites[]['sites']='http://naxumblog.com/'. $row['post_name'] .'.html';
		$sites[]['sites']='https://xpirient.news/'. $row['post_name'];
    }

    /*** close the database connection ***/
    $dbh = null;	
} catch(PDOException $e) {
    echo $e->getMessage();
}
echo json_encode($sites);
?>
