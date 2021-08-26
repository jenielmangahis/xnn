<?php
//include('wp-config.php');
include "../../hostname.php";
header("Access-Control-Allow-Origin: *");
$sitename = $_GET['sitename'];
try {
    $dbh = new PDO("mysql:host=$hostname;dbname=jelizabethblog", 'bloguser', 'bloguserpass',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    /*** echo a message saying we have connected ***/
    //echo 'Connected to database';

    $sql = "SELECT post_title, guid FROM wp_jelizabethblogposts WHERE `post_type`='attachment' AND `post_mime_type` LIKE '%application%'";

    foreach ($dbh->query($sql) as $row)
        {
          	$sites[]['sites']= $row['post_title'] .'*'. $row['guid'];
                // $siteOne[]['sites'] = "https://nxm.bz-/?url=https://www.jelizabethblog.com/".$row['post_name']."/&api=1ba8e9f15589548f5977fc2e22dafb0d";
		        // $sites[]['sites']= $row['guid'];
        }
    /*** close the database connection ***/
    $dbh = null;
    }
catch(PDOException $e)
    {
    echo $e->getMessage();
    }
echo json_encode($sites);

?>
