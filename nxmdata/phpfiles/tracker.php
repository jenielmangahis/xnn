<?php 

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	if(isset($_POST['process'])){
		try {
			$db = getConnection();	
			
			$sql = 'INSERT INTO traffictracking (userid,page,datelog,ipaddress) VALUES ("'.$_POST['sponsorid'].'","'.$_POST['page'].'","'.date("Y/m/d H:i:s").'","'.$_POST['ipaddress'].'")';
			$stmt = $db->prepare($sql);			
			$stmt->execute();
			$last_id =$db->lastInsertId();
			
			echo $last_id;
			
			$db = null;
		} catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	if(isset($_GET['type'])){
		$type = $_GET['type'];
		
		switch($type){
			case 'all':
				$traffic = getNewAll();
				echo $traffic;
				break;			
			case 'range':
				$datefrom = date('Y/m/d',strtotime($_GET['datefrom']));
				$dateto = date('Y/m/d',strtotime($_GET['dateto']));
				$traffic = getDateRange($datefrom,$dateto);
				echo $traffic;
				break;
			case 'monthly':
				$month=$_GET['month'];
				$year = $_GET['year'];
				$traffic = getMonthNew($month,$year);
				echo $traffic;
				break;
				
			case 'yearly':
				$year = $_GET['year'];
				$traffic = getYear($year);
				echo $traffic;
				break;
				
			default:
				echo '[{"error":"error"}]';
				break;
		}
	}
	
	function getYear($year){
		try{
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "select DATE(datelog) as d_day from traffictracking where userid=".$userid." and YEAR(datelog) = '".$year."'  group by DATE(datelog)";			
			//echo $sql;die();
			
			$stmt = $mdb->query($sql);
			$rows = $stmt->fetchAll();
			$row_count = count($rows);
			$tt ='[';
			$counter = 1;
			
			foreach ($rows as $row) {
				$drows = getDayNew($row['d_day']);
				
				
				$tm = '[';
					foreach ($drows as $rowm) {
						$tm .='{"richdad1.html":"'.$rowm['richdad1.html'].'"},';
						$tm .='{"christ.html":"'.$rowm['christ.html'].'"},';
						$tm .='{"coffee2.html":"'.$rowm['coffee2.html'].'"},';
						$tm .='{"energy.html":"'.$rowm['energy.html'].'"},';
						$tm .='{"energydrink.html":"'.$rowm['energydrink.html'].'"},';
						$tm .='{"freereportpage.html":"'.$rowm['freereportpage.html'].'"},';
						$tm .='{"gold.html":"'.$rowm['gold.html'].'"},';
						$tm .='{"coffee1.html":"'.$rowm['coffee1.html'].'"},';
						$tm .='{"health.html":"'.$rowm['health.html'].'"},';
						$tm .='{"homebiz.html":"'.$rowm['homebiz.html'].'"},';
						$tm .='{"index2.html":"'.$rowm['index2.html'].'"},';
						$tm .='{"index3.html":"'.$rowm['index3.html'].'"},';
						$tm .='{"legal1.html":"'.$rowm['legal1.html'].'"},';
						$tm .='{"legal2.html":"'.$rowm['legal2.html'].'"},';
						$tm .='{"military.html":"'.$rowm['military.html'].'"},';
						$tm .='{"mobile.html":"'.$rowm['mobile.html'].'"},';
						$tm .='{"mom.html":"'.$rowm['mom.html'].'"},';
						$tm .='{"richdad.html":"'.$rowm['richdad.html'].'"},';
						$tm .='{"tools.html":"'.$rowm['tools.html'].'"},';
						$tm .='{"travel1.html":"'.$rowm['travel1.html'].'"},';
						$tm .='{"tsr1.html":"'.$rowm['tsr1.html'].'"},';
						$tm .='{"tsr2.html":"'.$rowm['tsr2.html'].'"},';
						$tm .='{"weightloss.html":"'.$rowm['weight-loss.html'].'"},';
						$tm .='{"10-milindex.html":"'.$rowm['10-milindex.html'].'"},';
						$tm .='{"anti-aging.html":"'.$rowm['anti-aging.html'].'"},';
						$tm .='{"car-index.html":"'.$rowm['car-index.html'].'"},';
						$tm .='{"christian-index.html":"'.$rowm['christian-index.html'].'"},';
						$tm .='{"coffee-index.html":"'.$rowm['coffee-index.html'].'"},';
						$tm .='{"cpc-essential-oil.html":"'.$rowm['cpc-essential-oil.html'].'"},';
						$tm .='{"cpc-health-supplements.html":"'.$rowm['cpc-health-supplements.html'].'"},';
						$tm .='{"energy-2.html":"'.$rowm['energy-2.html'].'"},';
						$tm .='{"energy-drink2-index.html":"'.$rowm['energy-drink2-index.html'].'"},';
						$tm .='{"gold-index.html":"'.$rowm['gold-index.html'].'"},';
						$tm .='{"homebiz-index.html":"'.$rowm['homebiz-index.html'].'"},';
						$tm .='{"legal-index.html":"'.$rowm['legal-index.html'].'"},';
						$tm .='{"military-index.html":"'.$rowm['military-index.html'].'"},';
						$tm .='{"mobile-index.html":"'.$rowm['mobile-index.html'].'"},';
						$tm .='{"mom-index.html":"'.$rowm['mom-index.html'].'"},';
						$tm .='{"rich-index.html":"'.$rowm['rich-index.html'].'"},';
						$tm .='{"skin-care.html":"'.$rowm['skin-care.html'].'"},';
						$tm .='{"timefreedom.html":"'.$rowm['timefreedom.html'].'"},';
						$tm .='{"tools-index.html":"'.$rowm['tools-index.html'].'"},';
						$tm .='{"travel2-index.html":"'.$rowm['travel2-index.html'].'"},';
						$tm .='{"webinar-1-index.html":"'.$rowm['webinar-1-index.html'].'"},';
						$tm .='{"webinar-2-index.html":"'.$rowm['webinar-2-index.html'].'"},';
						$tm .='{"webinar-3-index.html":"'.$rowm['webinar-3-index.html'].'"},';
						$tm .='{"weightloss-index.html":"'.$rowm['weight-loss.html'].'"}';
						
					}
				$tm .= ']';
				if($counter == $row_count){
					$tt .= '{"'.$row['d_day'].'":'.$tm.'}';
				}else{
					$tt .= '{"'.$row['d_day'].'":'.$tm.'},';
				}
				
				
				$counter++;
			}
			$tt .= ']';
			
			
			//$traffic = $stmt->fetchAll(PDO::FETCH_OBJ);
			
			//var_dump($traffic);die();
			$mdb = null;
			
			return $tt;
			
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	function getMonthNew($month,$year){
		try{
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "select DATE(datelog) as d_day from traffictracking where userid=".$userid." and MONTH(datelog) = '".$month."' and YEAR(datelog) = '".$year."'  group by DATE(datelog)";			
			//echo $sql;die();
			
			$stmt = $mdb->query($sql);
			$rows = $stmt->fetchAll();
			$row_count = count($rows);
			$tt ='[';
			$counter = 1;
			
			foreach ($rows as $row) {
				$drows = getDayNew($row['d_day']);
				
				
				$tm = '[';
					foreach ($drows as $rowm) {
						$tm .='{"richdad1.html":"'.$rowm['richdad1.html'].'"},';
						$tm .='{"christ.html":"'.$rowm['christ.html'].'"},';
						$tm .='{"coffee2.html":"'.$rowm['coffee2.html'].'"},';
						$tm .='{"energy.html":"'.$rowm['energy.html'].'"},';
						$tm .='{"energydrink.html":"'.$rowm['energydrink.html'].'"},';
						$tm .='{"freereportpage.html":"'.$rowm['freereportpage.html'].'"},';
						$tm .='{"gold.html":"'.$rowm['gold.html'].'"},';
						$tm .='{"coffee1.html":"'.$rowm['coffee1.html'].'"},';
						$tm .='{"health.html":"'.$rowm['health.html'].'"},';
						$tm .='{"homebiz.html":"'.$rowm['homebiz.html'].'"},';
						$tm .='{"index2.html":"'.$rowm['index2.html'].'"},';
						$tm .='{"index3.html":"'.$rowm['index3.html'].'"},';
						$tm .='{"legal1.html":"'.$rowm['legal1.html'].'"},';
						$tm .='{"legal2.html":"'.$rowm['legal2.html'].'"},';
						$tm .='{"military.html":"'.$rowm['military.html'].'"},';
						$tm .='{"mobile.html":"'.$rowm['mobile.html'].'"},';
						$tm .='{"mom.html":"'.$rowm['mom.html'].'"},';
						$tm .='{"richdad.html":"'.$rowm['richdad.html'].'"},';
						$tm .='{"tools.html":"'.$rowm['tools.html'].'"},';
						$tm .='{"travel1.html":"'.$rowm['travel1.html'].'"},';
						$tm .='{"tsr1.html":"'.$rowm['tsr1.html'].'"},';
						$tm .='{"tsr2.html":"'.$rowm['tsr2.html'].'"},';
						$tm .='{"weightloss.html":"'.$rowm['weight-loss.html'].'"},';
						$tm .='{"10-milindex.html":"'.$rowm['10-milindex.html'].'"},';
						$tm .='{"anti-aging.html":"'.$rowm['anti-aging.html'].'"},';
						$tm .='{"car-index.html":"'.$rowm['car-index.html'].'"},';
						$tm .='{"christian-index.html":"'.$rowm['christian-index.html'].'"},';
						$tm .='{"coffee-index.html":"'.$rowm['coffee-index.html'].'"},';
						$tm .='{"cpc-essential-oil.html":"'.$rowm['cpc-essential-oil.html'].'"},';
						$tm .='{"cpc-health-supplements.html":"'.$rowm['cpc-health-supplements.html'].'"},';
						$tm .='{"energy-2.html":"'.$rowm['energy-2.html'].'"},';
						$tm .='{"energy-drink2-index.html":"'.$rowm['energy-drink2-index.html'].'"},';
						$tm .='{"gold-index.html":"'.$rowm['gold-index.html'].'"},';
						$tm .='{"homebiz-index.html":"'.$rowm['homebiz-index.html'].'"},';
						$tm .='{"legal-index.html":"'.$rowm['legal-index.html'].'"},';
						$tm .='{"military-index.html":"'.$rowm['military-index.html'].'"},';
						$tm .='{"mobile-index.html":"'.$rowm['mobile-index.html'].'"},';
						$tm .='{"mom-index.html":"'.$rowm['mom-index.html'].'"},';
						$tm .='{"rich-index.html":"'.$rowm['rich-index.html'].'"},';
						$tm .='{"skin-care.html":"'.$rowm['skin-care.html'].'"},';
						$tm .='{"timefreedom.html":"'.$rowm['timefreedom.html'].'"},';
						$tm .='{"tools-index.html":"'.$rowm['tools-index.html'].'"},';
						$tm .='{"travel2-index.html":"'.$rowm['travel2-index.html'].'"},';
						$tm .='{"webinar-1-index.html":"'.$rowm['webinar-1-index.html'].'"},';
						$tm .='{"webinar-2-index.html":"'.$rowm['webinar-2-index.html'].'"},';
						$tm .='{"webinar-3-index.html":"'.$rowm['webinar-3-index.html'].'"},';
						$tm .='{"weightloss-index.html":"'.$rowm['weight-loss.html'].'"}';
						
					}
				$tm .= ']';
				if($counter == $row_count){
					$tt .= '{"'.$row['d_day'].'":'.$tm.'}';
				}else{
					$tt .= '{"'.$row['d_day'].'":'.$tm.'},';
				}
				
				
				$counter++;
			}
			$tt .= ']';
			
			
			//$traffic = $stmt->fetchAll(PDO::FETCH_OBJ);
			
			//var_dump($traffic);die();
			$mdb = null;
			
			return $tt;
			
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	function getDateRange($datefrom,$dateto){
		try{
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "select DATE(datelog) as d_day from traffictracking where userid=".$userid." and (DATE(datelog) between DATE('".$datefrom."') and DATE('".$dateto."')) group by DATE(datelog)";			
			//echo $sql;die();
			
			$stmt = $mdb->query($sql);
			$rows = $stmt->fetchAll();
			$row_count = count($rows);
			$tt ='[';
			$counter = 1;
			
			foreach ($rows as $row) {
				$drows = getDayNew($row['d_day']);
				
				
				$tm = '[';
					foreach ($drows as $rowm) {
						$tm .='{"richdad1.html":"'.$rowm['richdad1.html'].'"},';
						$tm .='{"christ.html":"'.$rowm['christ.html'].'"},';
						$tm .='{"coffee2.html":"'.$rowm['coffee2.html'].'"},';
						$tm .='{"energy.html":"'.$rowm['energy.html'].'"},';
						$tm .='{"energydrink.html":"'.$rowm['energydrink.html'].'"},';
						$tm .='{"freereportpage.html":"'.$rowm['freereportpage.html'].'"},';
						$tm .='{"gold.html":"'.$rowm['gold.html'].'"},';
						$tm .='{"coffee1.html":"'.$rowm['coffee1.html'].'"},';
						$tm .='{"health.html":"'.$rowm['health.html'].'"},';
						$tm .='{"homebiz.html":"'.$rowm['homebiz.html'].'"},';
						$tm .='{"index2.html":"'.$rowm['index2.html'].'"},';
						$tm .='{"index3.html":"'.$rowm['index3.html'].'"},';
						$tm .='{"legal1.html":"'.$rowm['legal1.html'].'"},';
						$tm .='{"legal2.html":"'.$rowm['legal2.html'].'"},';
						$tm .='{"military.html":"'.$rowm['military.html'].'"},';
						$tm .='{"mobile.html":"'.$rowm['mobile.html'].'"},';
						$tm .='{"mom.html":"'.$rowm['mom.html'].'"},';
						$tm .='{"richdad.html":"'.$rowm['richdad.html'].'"},';
						$tm .='{"tools.html":"'.$rowm['tools.html'].'"},';
						$tm .='{"travel1.html":"'.$rowm['travel1.html'].'"},';
						$tm .='{"tsr1.html":"'.$rowm['tsr1.html'].'"},';
						$tm .='{"tsr2.html":"'.$rowm['tsr2.html'].'"},';
						$tm .='{"weightloss.html":"'.$rowm['weight-loss.html'].'"},';
						$tm .='{"10-milindex.html":"'.$rowm['10-milindex.html'].'"},';
						$tm .='{"anti-aging.html":"'.$rowm['anti-aging.html'].'"},';
						$tm .='{"car-index.html":"'.$rowm['car-index.html'].'"},';
						$tm .='{"christian-index.html":"'.$rowm['christian-index.html'].'"},';
						$tm .='{"coffee-index.html":"'.$rowm['coffee-index.html'].'"},';
						$tm .='{"cpc-essential-oil.html":"'.$rowm['cpc-essential-oil.html'].'"},';
						$tm .='{"cpc-health-supplements.html":"'.$rowm['cpc-health-supplements.html'].'"},';
						$tm .='{"energy-2.html":"'.$rowm['energy-2.html'].'"},';
						$tm .='{"energy-drink2-index.html":"'.$rowm['energy-drink2-index.html'].'"},';
						$tm .='{"gold-index.html":"'.$rowm['gold-index.html'].'"},';
						$tm .='{"homebiz-index.html":"'.$rowm['homebiz-index.html'].'"},';
						$tm .='{"legal-index.html":"'.$rowm['legal-index.html'].'"},';
						$tm .='{"military-index.html":"'.$rowm['military-index.html'].'"},';
						$tm .='{"mobile-index.html":"'.$rowm['mobile-index.html'].'"},';
						$tm .='{"mom-index.html":"'.$rowm['mom-index.html'].'"},';
						$tm .='{"rich-index.html":"'.$rowm['rich-index.html'].'"},';
						$tm .='{"skin-care.html":"'.$rowm['skin-care.html'].'"},';
						$tm .='{"timefreedom.html":"'.$rowm['timefreedom.html'].'"},';
						$tm .='{"tools-index.html":"'.$rowm['tools-index.html'].'"},';
						$tm .='{"travel2-index.html":"'.$rowm['travel2-index.html'].'"},';
						$tm .='{"webinar-1-index.html":"'.$rowm['webinar-1-index.html'].'"},';
						$tm .='{"webinar-2-index.html":"'.$rowm['webinar-2-index.html'].'"},';
						$tm .='{"webinar-3-index.html":"'.$rowm['webinar-3-index.html'].'"},';
						$tm .='{"weightloss-index.html":"'.$rowm['weight-loss.html'].'"}';
						
					}
				$tm .= ']';
				if($counter == $row_count){
					$tt .= '{"'.$row['d_day'].'":'.$tm.'}';
				}else{
					$tt .= '{"'.$row['d_day'].'":'.$tm.'},';
				}
				
				
				$counter++;
			}
			$tt .= ']';
			
			
			//$traffic = $stmt->fetchAll(PDO::FETCH_OBJ);
			
			//var_dump($traffic);die();
			$mdb = null;
			
			return $tt;
			
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	function getNewAll(){
		try {
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "select DATE(datelog) as d_day from traffictracking where userid=".$userid." group by DATE(datelog)";			
			$stmt = $mdb->query($sql);
			$rows = $stmt->fetchAll();
			$row_count = count($rows);
			$tt ='[';
			$counter = 1;
			
			foreach ($rows as $row) {
				$drows = getDayNew($row['d_day']);
				
				
				$tm = '[';
					foreach ($drows as $rowm) {
						$tm .='{"richdad1.html":"'.$rowm['richdad1.html'].'"},';
						$tm .='{"christ.html":"'.$rowm['christ.html'].'"},';
						$tm .='{"coffee2.html":"'.$rowm['coffee2.html'].'"},';
						$tm .='{"energy.html":"'.$rowm['energy.html'].'"},';
						$tm .='{"energydrink.html":"'.$rowm['energydrink.html'].'"},';
						$tm .='{"freereportpage.html":"'.$rowm['freereportpage.html'].'"},';
						$tm .='{"gold.html":"'.$rowm['gold.html'].'"},';
						$tm .='{"coffee1.html":"'.$rowm['coffee1.html'].'"},';
						$tm .='{"health.html":"'.$rowm['health.html'].'"},';
						$tm .='{"homebiz.html":"'.$rowm['homebiz.html'].'"},';
						$tm .='{"index2.html":"'.$rowm['index2.html'].'"},';
						$tm .='{"index3.html":"'.$rowm['index3.html'].'"},';
						$tm .='{"legal1.html":"'.$rowm['legal1.html'].'"},';
						$tm .='{"legal2.html":"'.$rowm['legal2.html'].'"},';
						$tm .='{"military.html":"'.$rowm['military.html'].'"},';
						$tm .='{"mobile.html":"'.$rowm['mobile.html'].'"},';
						$tm .='{"mom.html":"'.$rowm['mom.html'].'"},';
						$tm .='{"richdad.html":"'.$rowm['richdad.html'].'"},';
						$tm .='{"tools.html":"'.$rowm['tools.html'].'"},';
						$tm .='{"travel1.html":"'.$rowm['travel1.html'].'"},';
						$tm .='{"tsr1.html":"'.$rowm['tsr1.html'].'"},';
						$tm .='{"tsr2.html":"'.$rowm['tsr2.html'].'"},';
						$tm .='{"weightloss.html":"'.$rowm['weight-loss.html'].'"},';
						$tm .='{"10-milindex.html":"'.$rowm['10-milindex.html'].'"},';
						$tm .='{"anti-aging.html":"'.$rowm['anti-aging.html'].'"},';
						$tm .='{"car-index.html":"'.$rowm['car-index.html'].'"},';
						$tm .='{"christian-index.html":"'.$rowm['christian-index.html'].'"},';
						$tm .='{"coffee-index.html":"'.$rowm['coffee-index.html'].'"},';
						$tm .='{"cpc-essential-oil.html":"'.$rowm['cpc-essential-oil.html'].'"},';
						$tm .='{"cpc-health-supplements.html":"'.$rowm['cpc-health-supplements.html'].'"},';
						$tm .='{"energy-2.html":"'.$rowm['energy-2.html'].'"},';
						$tm .='{"energy-drink2-index.html":"'.$rowm['energy-drink2-index.html'].'"},';
						$tm .='{"gold-index.html":"'.$rowm['gold-index.html'].'"},';
						$tm .='{"homebiz-index.html":"'.$rowm['homebiz-index.html'].'"},';
						$tm .='{"legal-index.html":"'.$rowm['legal-index.html'].'"},';
						$tm .='{"military-index.html":"'.$rowm['military-index.html'].'"},';
						$tm .='{"mobile-index.html":"'.$rowm['mobile-index.html'].'"},';
						$tm .='{"mom-index.html":"'.$rowm['mom-index.html'].'"},';
						$tm .='{"rich-index.html":"'.$rowm['rich-index.html'].'"},';
						$tm .='{"skin-care.html":"'.$rowm['skin-care.html'].'"},';
						$tm .='{"timefreedom.html":"'.$rowm['timefreedom.html'].'"},';
						$tm .='{"tools-index.html":"'.$rowm['tools-index.html'].'"},';
						$tm .='{"travel2-index.html":"'.$rowm['travel2-index.html'].'"},';
						$tm .='{"webinar-1-index.html":"'.$rowm['webinar-1-index.html'].'"},';
						$tm .='{"webinar-2-index.html":"'.$rowm['webinar-2-index.html'].'"},';
						$tm .='{"webinar-3-index.html":"'.$rowm['webinar-3-index.html'].'"},';
						$tm .='{"weightloss-index.html":"'.$rowm['weight-loss.html'].'"}';
						
					}
				$tm .= ']';
				if($counter == $row_count){
					$tt .= '{"'.$row['d_day'].'":'.$tm.'}';
				}else{
					$tt .= '{"'.$row['d_day'].'":'.$tm.'},';
				}
				
				
				$counter++;
			}
			$tt .= ']';
			
			
			//$traffic = $stmt->fetchAll(PDO::FETCH_OBJ);
			
			//var_dump($traffic);die();
			$mdb = null;
			
			return $tt;
		
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	function getAll(){
		
		try {
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "
				SELECT 
					(SELECT COUNT(id) FROM traffictracking where page='richdad1' and userid=".$userid.") as 'richdad1.html',
					(SELECT COUNT(id) FROM traffictracking where page='christ' and userid=".$userid.") as 'christ.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee1' and userid=".$userid.") as 'coffee1.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee2' and userid=".$userid.") as 'coffee2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy' and userid=".$userid.") as 'energy.html',
					(SELECT COUNT(id) FROM traffictracking where page='energydrink' and userid=".$userid.") as 'energydrink.html',
					(SELECT COUNT(id) FROM traffictracking where page='freereportpage') as 'freereportpage.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold' and userid=".$userid.") as 'gold.html',
					(SELECT COUNT(id) FROM traffictracking where page='health' and userid=".$userid.") as 'health.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz' and userid=".$userid.") as 'homebiz.html',
					(SELECT COUNT(id) FROM traffictracking where page='index2' and userid=".$userid.") as 'index2.html',
					(SELECT COUNT(id) FROM traffictracking where page='index3' and userid=".$userid.") as 'index3.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal1' and userid=".$userid.") as 'legal1.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal2' and userid=".$userid.") as 'legal2.html',
					(SELECT COUNT(id) FROM traffictracking where page='military' and userid=".$userid.") as 'military.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile' and userid=".$userid.") as 'mobile.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom' and userid=".$userid.") as 'mom.html',
					(SELECT COUNT(id) FROM traffictracking where page='richdad' and userid=".$userid.") as 'richdad.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools' and userid=".$userid.") as 'tools.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel1' and userid=".$userid.") as 'travel1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr1' and userid=".$userid.") as 'tsr1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr2' and userid=".$userid.") as 'tsr2.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss' and userid=".$userid.") as 'weightloss.html',
					(SELECT COUNT(id) FROM traffictracking where page='10-milindex' and userid=".$userid.") as '10-milindex.html',
					(SELECT COUNT(id) FROM traffictracking where page='anti-aging' and userid=".$userid.") as 'anti-aging.html',
					(SELECT COUNT(id) FROM traffictracking where page='car-index' and userid=".$userid.") as 'car-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='christian-index' and userid=".$userid.") as 'christian-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee-index' and userid=".$userid.") as 'coffee-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-2' and userid=".$userid.") as 'energy-2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-drink2-index' and userid=".$userid.") as 'energy-drink2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold-index' and userid=".$userid.") as 'gold-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz-index' and userid=".$userid.") as 'homebiz-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal-index' and userid=".$userid.") as 'legal-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='military-index' and userid=".$userid.") as 'military-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile-index' and userid=".$userid.") as 'mobile-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom-index' and userid=".$userid.") as 'mom-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='rich-index' and userid=".$userid.") as 'rich-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='skin-care' and userid=".$userid.") as 'skin-care.html',
					(SELECT COUNT(id) FROM traffictracking where page='timefreedom' and userid=".$userid.") as 'timefreedom.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools-index' and userid=".$userid.") as 'tools-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel2-index' and userid=".$userid.") as 'travel2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-1-index' and userid=".$userid.") as 'webinar-1-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-2-index' and userid=".$userid.") as 'webinar-2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-3-index' and userid=".$userid.") as 'webinar-3-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss-index' and userid=".$userid.") as 'weight-loss.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-essential-oil' and userid=".$userid.") as 'cpc-essential-oil.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-health-supplements' and userid=".$userid.") as 'cpc-health-supplements.html'
			";
				
			$stmt = $mdb->query($sql);
			$traffic = $stmt->fetchAll(PDO::FETCH_OBJ);
			$mdb = null;
			
			return $traffic;
		
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	function getDay(){
		try {
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "
				SELECT 
					(SELECT COUNT(id) FROM traffictracking where page='richdad1' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'richdad1.html',
					(SELECT COUNT(id) FROM traffictracking where page='christ' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'christ.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee1' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'coffee1.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee2' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'coffee2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'energy.html',
					(SELECT COUNT(id) FROM traffictracking where page='energydrink' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'energydrink.html',
					(SELECT COUNT(id) FROM traffictracking where page='freereportpage' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'freereportpage.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'gold.html',
					(SELECT COUNT(id) FROM traffictracking where page='health' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'health.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'homebiz.html',
					(SELECT COUNT(id) FROM traffictracking where page='index2' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'index2.html',
					(SELECT COUNT(id) FROM traffictracking where page='index3' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'index3.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal1' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'legal1.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal2' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'legal2.html',
					(SELECT COUNT(id) FROM traffictracking where page='military' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'military.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'mobile.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'mom.html',
					(SELECT COUNT(id) FROM traffictracking where page='richdad' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'richdad.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'tools.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel1' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'travel1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr1' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'tsr1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr2' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'tsr2.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'weightloss.html',
					(SELECT COUNT(id) FROM traffictracking where page='10-milindex' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as '10-milindex.html',
					(SELECT COUNT(id) FROM traffictracking where page='anti-aging' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'anti-aging.html',
					(SELECT COUNT(id) FROM traffictracking where page='car-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'car-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='christian-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'christian-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'coffee-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-2' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'energy-2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-drink2-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'energy-drink2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'gold-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'homebiz-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'legal-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='military-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'military-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'mobile-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'mom-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='rich-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'rich-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='skin-care' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'skin-care.html',
					(SELECT COUNT(id) FROM traffictracking where page='timefreedom' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'timefreedom.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'tools-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel2-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'travel2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-1-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'webinar-1-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-2-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'webinar-2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-3-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'webinar-3-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss-index' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'weight-loss.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-essential-oil' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'cpc-essential-oil.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-health-supplements' and userid=".$userid." and DATE(datelog) = DATE(NOW())) as 'cpc-health-supplements.html'
			";
				
			$stmt = $mdb->query($sql);
			$traffic = $stmt->fetchAll(PDO::FETCH_OBJ);
			$mdb = null;
			
			return $traffic;
		
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	function getDayNew($dday){
		try {
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "
				SELECT 
					(SELECT COUNT(id) FROM traffictracking where page='richdad1' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'richdad1.html',
					(SELECT COUNT(id) FROM traffictracking where page='christ' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'christ.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee1' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'coffee1.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee2' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'coffee2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'energy.html',
					(SELECT COUNT(id) FROM traffictracking where page='energydrink' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'energydrink.html',
					(SELECT COUNT(id) FROM traffictracking where page='freereportpage' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'freereportpage.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'gold.html',
					(SELECT COUNT(id) FROM traffictracking where page='health' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'health.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'homebiz.html',
					(SELECT COUNT(id) FROM traffictracking where page='index2' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'index2.html',
					(SELECT COUNT(id) FROM traffictracking where page='index3' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'index3.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal1' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'legal1.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal2' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'legal2.html',
					(SELECT COUNT(id) FROM traffictracking where page='military' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'military.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'mobile.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'mom.html',
					(SELECT COUNT(id) FROM traffictracking where page='richdad' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'richdad.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'tools.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel1' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'travel1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr1' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'tsr1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr2' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'tsr2.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'weightloss.html',
					(SELECT COUNT(id) FROM traffictracking where page='10-milindex' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as '10-milindex.html',
					(SELECT COUNT(id) FROM traffictracking where page='anti-aging' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'anti-aging.html',
					(SELECT COUNT(id) FROM traffictracking where page='car-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'car-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='christian-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'christian-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'coffee-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-2' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'energy-2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-drink2-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'energy-drink2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'gold-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'homebiz-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'legal-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='military-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'military-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'mobile-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'mom-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='rich-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'rich-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='skin-care' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'skin-care.html',
					(SELECT COUNT(id) FROM traffictracking where page='timefreedom' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'timefreedom.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'tools-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel2-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'travel2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-1-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'webinar-1-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-2-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'webinar-2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-3-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'webinar-3-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss-index' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'weight-loss.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-essential-oil' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'cpc-essential-oil.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-health-supplements' and userid=".$userid." and DATE(datelog) = DATE('".$dday."')) as 'cpc-health-supplements.html'
			";
			
			$stmt = $mdb->query($sql);
			//$traffic = $stmt->fetchAll();
			$mdb = null;
			
			return $stmt;
		
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
	
	function getMonth(){
		
		try {
			$userid = $_GET['userid'];
			$mdb = getConnection();
			$sql = "
				SELECT 
					(SELECT COUNT(id) FROM traffictracking where page='richdad1' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'richdad1.html',
					(SELECT COUNT(id) FROM traffictracking where page='christ' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'christ.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee1' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'coffee1.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee2' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'coffee2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'energy.html',
					(SELECT COUNT(id) FROM traffictracking where page='energydrink' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'energydrink.html',
					(SELECT COUNT(id) FROM traffictracking where page='freereportpage' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'freereportpage.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'gold.html',
					(SELECT COUNT(id) FROM traffictracking where page='health' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'health.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'homebiz.html',
					(SELECT COUNT(id) FROM traffictracking where page='index2' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'index2.html',
					(SELECT COUNT(id) FROM traffictracking where page='index3' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'index3.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal1' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'legal1.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal2' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'legal2.html',
					(SELECT COUNT(id) FROM traffictracking where page='military' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'military.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'mobile.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'mom.html',
					(SELECT COUNT(id) FROM traffictracking where page='richdad' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'richdad.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'tools.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel1' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'travel1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr1' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'tsr1.html',
					(SELECT COUNT(id) FROM traffictracking where page='tsr2' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'tsr2.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'weightloss.html',
					(SELECT COUNT(id) FROM traffictracking where page='10-milindex' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as '10-milindex.html',
					(SELECT COUNT(id) FROM traffictracking where page='anti-aging' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'anti-aging.html',
					(SELECT COUNT(id) FROM traffictracking where page='car-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'car-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='christian-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'christian-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='coffee-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'coffee-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-2' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'energy-2.html',
					(SELECT COUNT(id) FROM traffictracking where page='energy-drink2-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'energy-drink2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='gold-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'gold-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='homebiz-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'homebiz-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='legal-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'legal-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='military-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'military-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mobile-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'mobile-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='mom-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'mom-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='rich-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'rich-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='skin-care' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'skin-care.html',
					(SELECT COUNT(id) FROM traffictracking where page='timefreedom' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'timefreedom.html',
					(SELECT COUNT(id) FROM traffictracking where page='tools-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'tools-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='travel2-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'travel2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-1-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'webinar-1-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-2-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'webinar-2-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='webinar-3-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'webinar-3-index.html',
					(SELECT COUNT(id) FROM traffictracking where page='weightloss-index' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'weight-loss.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-essential-oil' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'cpc-essential-oil.html',    
					(SELECT COUNT(id) FROM traffictracking where page='cpc-health-supplements' and userid=".$userid." and YEAR(datelog)=YEAR(NOW()) and MONTH(datelog)=MONTH(NOW())) as 'cpc-health-supplements.html'
			";
				
			$stmt = $mdb->query($sql);
			$traffic = $stmt->fetchAll(PDO::FETCH_OBJ);
			$mdb = null;
			
			return $traffic;
		
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
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