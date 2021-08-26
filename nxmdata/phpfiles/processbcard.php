<?php 
	
	if(isset($_POST['process_text'])){
					
		try {
			$db = getConnection();	
			
			$sql = 'INSERT INTO digitalcarddata (userid,firstname,lastname,mobilenumber) VALUES ("'.$_POST['sponsorid'].'","'.$_POST['fname'].'","'.$_POST['lname'].'","'.$_POST['cellphone'].'")';
			$stmt = $db->prepare($sql);			
			$stmt->execute();
			$last_id =$db->lastInsertId();
			
			$sql2 = 'INSERT INTO textmessages (
				userid,
				sentfrom,
				sendto,
				message,
				sent,
				recipient,
				datesent) VALUES (
				"'.$_POST['sponsorid'].'",
				"'.$_POST['sponsorid'].'",
				"'.$_POST['cellphone'].'",
				"Hi '.$_POST['fname'].', i just started a new business and wanted to share my digital business card with you. http://'.$_POST['sitename'].'.toolsrock.com/digitalcard.html?pid='.$last_id.' . ",
				"0",
				"'.$_POST['cellphone'].'",
				"'.date("Y/m/d H:i:s").'"
				)';
			
			$stmt2 = $db->prepare($sql2);			
			$stmt2->execute();
			$last_id =$db->lastInsertId();
			
			echo $last_id;
			
			$db = null;
		} catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
		
	}
	
	if(isset($_GET['f'])){
		if($_GET['f'] == 'getdata'){
			try {
				$db = getConnection();	
				
				$sql = "SELECT * FROM digitalcarddata WHERE id=" . $_GET['pid'];
				$stmt = $db->prepare($sql);  
				$stmt->execute();
				$data = $stmt->fetchObject();  
				$db = null;
				echo json_encode($data); 
				
				$db = null;
			} catch(PDOException $e) {
				echo '{"error":{"text":'. $e->getMessage() .'}}'; 
			}
		}
	}
	
	function getConnection() {
		$dbhost="dbserver";
		$dbuser="jvjunsay";
		$dbpass="agdcmnLw83du2fSnkcZaTjTr0";
		$dbname="tsr";
		$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}
?>