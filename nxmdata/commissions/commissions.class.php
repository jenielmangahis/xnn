<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/db.config.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/includes/DB.class.new.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/commissions/commission.config.php');

class Commission{
	
	protected $db;
	protected $db2;
	protected $period_id;
	protected $start_date;
	protected $end_date;
	
	public function __construct($test=false){	
		
		$this->db = Database::getInstance()->getDB();			
	}
	
	public function setPeriodId($period_id) {
		
		$this->period_id = $period_id;
		$this->setPeriodStartDate();
		$this->setPeriodEndDate();
	}
	
	public function getPeriodStartDate() {

		$query = "SELECT start_date FROM cm_commission_periods WHERE commission_period_id =" . $this->period_id;
		$smt = $this->db->prepare($query);
		$smt->execute();
		$result = $smt->fetch(PDO::FETCH_ASSOC);
		
		return $result['start_date'];
	}
	
	public function getPeriodEndDate(){

		$query = "Select end_date from cm_commission_periods where commission_period_id= :period_id";		
		$smt = $this->db->prepare($query);
		$smt->bindParam('period_id',$this->period_id);
		$smt->execute();
		$result = $smt->fetch(PDO::FETCH_ASSOC);

		return $result['end_date'];
	}
	
	private function setPeriodStartDate(){

		$this->start_date = $this->getPeriodStartDate();
	}
	
	private function setPeriodEndDate(){

		$this->end_date = $this->getPeriodEndDate();
	}
	
	private function getPayouts(){
		$key = 'DEFAULT';

		$smt = $this->db->prepare("CALL sp_fetchPayout(:key, :period_id);");
		$smt->bindParam(':key', $key);
		$smt->bindParam(':period_id', $this->period_id);
		$smt->execute();
		$result = $smt->fetchAll(PDO::FETCH_ASSOC);
	
		return $result;			
	} // getPayouts
	
	private function getPayoutDetails(){
		$key = 'DETAILS';

        $smt = $this->db->prepare("CALL sp_fetchPayout(:key, :period_id);");
		$smt->bindParam(':key', $key);
		$smt->bindParam(':period_id', $this->period_id);
		$smt->execute();
		$result = $smt->fetchAll(PDO::FETCH_ASSOC);

		return $result;		
	} // getPayoutDetails

	public function resetCommission($period_id = null) {
		
		$query  = "CALL sp_unpostCommission(:period_id)";
		$smt = $this->db->prepare($query);	
		$smt->bindParam(':period_id', $period_id);
		$smt->execute();
	} // resetCommission
	
	public function generateReport($type = null, $period_id = null) {
		$commissionTypeName = $this->getCommissionTypeName($type);
		$start = date('YFj', strtotime($this->start_date));
		$end = date('YFj', strtotime($this->end_date));
		$ctype = str_replace(' ','', $commissionTypeName);
		$fileName = strtolower($ctype . "_" . $start . "-" . $end . "_payouts.csv");
		$fileNameDetails = strtolower($ctype . "_" . $start . "-" . $end . "_payout_details.csv");
        $dateRange = " (" . $start . "-" . $end . ")";
		
		$payouts = $this->getPayouts();
        $payoutDetails = $this->getPayoutDetails();

		if (!empty($payouts) || !empty($payoutDetails)) {
			
			if ($type) {

				$details = "Download Commission Payouts " . $commissionTypeName . $dateRange;
				$this->exportToCSV(
							 $payouts 	// $data
							,$fileName 	// $filename
							,$details 	// $details
						);

				$details = "Download Commission Payouts Details " . $commissionTypeName . $dateRange;
				$this->exportToCSV(
							 $payoutDetails 	// $data
							,$fileNameDetails 	// $filename
							,$details 		 	// $details
						);
			} else {

				$details = "Download Commission Payouts ". $dateRange;
				$this->exportToCSV(
							 $payouts   // $data
							,$fileName 	// $filename
							,$details 	// $details
						);

				$details = "Download Commission Payout Details " . $dateRange;
				$this->exportToCSV(
							 $payoutDetails 	// $data
							,$fileNameDetails 	// $filename
							,$details 			// $details
						);
			}
		} else {
			print "NO COMMISSION REPORT!"; 
		}
	} // generateReport	

    public function generatePayouts($period_id){

        $query = 
        	"SELECT
    			  cp.commission_payout_id 					AS payout_id
				, m.lname 									AS first_name
				, m.fname 									AS last_name
				, m.business 								AS bussiness_name
				, m.site 									AS user_name
				, m.active 									AS active
				, m.id 										AS member_id
				, ROUND(SUM(cp.value), 2) 					AS total_payout
				, pp.business 								AS business
				, pp.firstname 								AS bus_fname
				, pp.lastname 								AS bus_lname
				, IF(
						  pp.type IS NOT NULL
						, pp.type
						, ''
					) 										AS payment_type
				, IF(
						  pp.routing_number IS NOT NULL
						, LPAD(pp.routing_number,9,'0')
						, '000000000'
					) 										AS routing_number
				, IF(
						  pp.account_number IS NOT NULL
						, LPAD(pp.account_number,9,'0')
						, '000000000'
					) 										AS account_number
            FROM users AS m
            JOIN cm_commission_payouts AS cp 	ON ( m.id = cp.user_id )
            JOIN cm_commission_periods AS p 	ON (cp.commission_period_id = p.commission_period_id)
            LEFT JOIN payment_page AS pp 		ON (m.id = pp.user_id)
            WHERE (cp.commission_period_id = :period_id) AND 
            	  (p.locked = 1) AND 
            	  (cp.is_paid = 0)
            GROUP BY cp.user_id
            ORDER BY total_payout DESC";

        $smt = $this->db->prepare($query);
        $smt->bindParam('period_id', $period_id);
        $smt->execute();
        $payouts = $smt->fetchAll(PDO::FETCH_ASSOC);

        return $payouts;
    } /* generatePayouts */



	


	protected function doCommissionPayout($details){
		
       // var_dump($details);die();
		$this->db->beginTransaction();
		$query1 = "insert into cm_commission_payouts(commission_payout_type_id,user_id,commission_period_id,level,value)
							values(:payout_type,:id,:period_id,:level,:value)";
							
		$query2 = "insert into cm_commission_payout_details(commission_payout_id,order_id,user_id,level,value,percent,amount,commission_order_id)
								values(:payout_id,:order_id,:id,:level,:value,:percent,:amount,:commission_order_id)";
	    $detail['value'] = round( $detail['value'], 2);

       // echo count($details);die();
		for($i=0;$i<count($details);$i++){
			$detail = $details[$i];
							
			$stmt = $this->db->prepare($query1);
			$stmt->bindParam('payout_type',$detail['payout_type']);				
			$stmt->bindParam('id',$detail['sponsor_id']);
			if(array_key_exists('period_id',$detail))
				$stmt->bindParam('period_id',$detail['period_id']);				
			else	
				$stmt->bindParam('period_id',$this->period_id);
				
			$stmt->bindParam('level',$detail['level']);	
			$stmt->bindParam('value', $detail['value'] );				
			
			$stmt->execute();
			$payout_id = $this->db->lastInsertId();
			
			if(array_key_exists('order_details',$detail)){
				$orders = $detail['order_details'];
								
				foreach($orders as $o){
                    $perc = $detail['percentage'] * 100 ;
					$stmt2 = $this->db->prepare($query2);
					$stmt2->bindParam('payout_id',$payout_id);
					$stmt2->bindParam('order_id',$o['order_id']);
					$stmt2->bindParam('id',$o['business_center_id']);
					$stmt2->bindParam('level',$detail['level']);
					$stmt2->bindParam('value',$detail['commission']);
					$stmt2->bindParam('percent',$perc);
					$stmt2->bindParam('amount',$detail['value']);
                    $stmt2->bindParam('commission_order_id',$detail['commission_order_id']);
					$stmt2->execute();
				}
			}else if($detail['business_center_id'] != 0){
					$perc = $detail['percentage'] * 100 ;
					$stmt2 = $this->db->prepare($query2);
					$stmt2->bindParam('payout_id',$payout_id);
					$stmt2->bindParam('order_id',$detail['order_id']);
					$stmt2->bindParam('id',$detail['business_center_id']);
					$stmt2->bindParam('level',$detail['level']);
					$stmt2->bindParam('value',$detail['commission']);
					$stmt2->bindParam('percent',$perc);
					$stmt2->bindParam('amount',$detail['value']);
                    $stmt2->bindParam('commission_order_id',$detail['commission_order_id']);
					$stmt2->execute();
			}
			if(array_key_exists('product',$detail))
				$this->saveProductPayout($payout_id,$detail['product']);
		}
		$this->db->commit();


	}
	
	protected function saveProductPayout($payout_id,$product_id){
		$query = "insert into cm_commission_payout_product(commission_payout_id,product_id) values(:payout_id,:product_id)";
				
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':payout_id',$payout_id);	
		$stmt->bindParam(':product_id',$product_id);
		$stmt->execute();	
	}
	
	public function getLatestPeriodId($period_type,$locked=1){		
		$query = "Select cm_commission_period_id 
						FROM cm_commission_periods 
						WHERE commission_period_type_id=:period_type and locked=:locked
						AND end_date = ( 
							SELECT MAX( end_date ) 
							FROM cm_commission_periods
							WHERE commission_period_type_id =:period_type)";
		
		$smt = $this->db->prepare($query);
		$this->db->bind(":period_type",$period_type);
		$this->db->bind(":locked",$locked);
		$result = $smt->fetch(PDO::FETCH_ASSOC);
		
		return $result['commission_period_id'];
	}
	
	protected function getPeriodIDByDate($period_type,$startDate,$endDate){
		$query = "SELECT cm_commission_period_id from cm_commission_periods 
							WHERE commission_period_type_id = :period_type 
							AND start_date = :startDate AND end_date = :endDate";
		
		$smt = $this->db->prepare($query);
		$this->db->bind(":period_type",$period_type);
		$this->db->bind(":startDate",$startDate);
		$this->db->bind(":endDate",$endDate);
		$result = $smt->fetch(PDO::FETCH_ASSOC);
		
		return $result['commission_period_id'];
		
	}
	
	protected function getPreviousPeriodId($period_type){
		$query = "select cm_commission_period_id from cm_commission_periods
								where start_date < (
									select start_date from cm_commission_periods
									where commission_period_id = :period_id
								)
								and commission_period_type_id = :period_type
								order by start_date desc
								limit 1";
		
		$smt = $this->db->prepare($query);			
		$this->db->bind(':period_type',$period_type);	
		$this->db->bind(':period_id',$this->period_id);	
		
		$result = $smt->fetch(PDO::FETCH_ASSOC);							
		return $result['commission_period_id'];
	}
	
	/*
	Matrix or Binary = 1
	Sponsor tree = 2		
	*/
	public function getParent($distributor_id,$tree_type){
		$query = "Select member_id from nodes where tree_id=:tree_id 
							and node_id=(Select parent_id from nodes 
							where tree_id=:tree_id and member_id=:distributor_id)";
				
		$smt = $this->db->prepare($query);	
		$this->db->bind(':tree_id',$tree_type);	
		$this->db->bind(':distributor_id',$distributor_id);	
		
		$result = $smt->fetch(PDO::FETCH_ASSOC);							
		return $result['member_id'];								
	}
	
	//Add sponsor_id field in customer table
	public function getCustomerSponsor($customer_id){
		
		$query = "Select sponsor_id from customer where customer_id=:customer_id";
		$this->db->query($query);			
		$this->db->bind(':customer_id',$customer_id);		
		$result = $this->db->fieldValue();
		
		return $result['sponsor_id'];	
	}
	
	public function getCurrentRank($distributor_id){
		$query = "Select commission_rank_id from current_ranks where member_id=:distributor_id";
		
		$this->db->query($query);			
		$this->db->bind(':distributor_id',$distributor_id);	
		
		$result = $this->db->fieldValue();							
		return $result['commission_rank_id'];
	}
	
	protected function getRank($distributor_id){
	
		$query ="Select commission_rank_id from commission_period_ranks 
					where member_id=:distributor_id and commission_period_id=:period_id";
		
		$this->db->query($query);			
		$this->db->bind(':distributor_id',$distributor_id);	
		$this->db->bind(':period_id',$this->period_id);	
		
		$result = $this->db->fieldValue();							
		return $result['commission_rank_id'];
	}
	
	public function getHighestRank($distributor_id){
		$query = "Select max(commission_rank_id) as max_rank
						from commission_period_ranks cpr join commission_periods cp using(commission_period_id) 
						where cpr.member_id=:bcid and cp.end_date between :start and :end";
		
		$this->db->query($query);			
		$this->db->bind(':bcid',$distributor_id);	
		$this->db->bind(':start',$this->getPeriodStartDate());
		$this->db->bind(':end',$this->getPeriodEndDate());
		
		$result = $this->db->fieldValue();							
		return $result['max_rank'];
	}
	
	protected function updateCurrentRank($bcid,$rank_id){		
		$query = "Update current_ranks set commission_rank_id=:rank_id where member_id=:distributor_id";
		
		$this->db->query($query);
		$this->db->bind(':rank_id',$rank_id);	
		$this->db->bind(':distributor_id',$bcid);
		$database->execute();
	}
	
	protected function addPeriodRank($bcid,$rank_id,$pid=null){
		if($pid)
			$period_id = $pid;
		else
			$period_id = $this->period_id;
		
		
		$count = $this->getPeriodRankCount($bcid,$period_id);
		
		if($count <= 0)
			$this->newPeriodRank($bcid,$period_id,$rank_id);
		
		else
			$this->updatePeriodRank($rank_id,$bcid,$period_id);
		
	}

    protected function get_refund(){
        $query = "Select cpr.user_id as bc_id,cmo.passup_sponsor_id as sponsor_id, cpr.amount,cpr.order_id,cmo.id as commission_order_id
						from cm_commission_payout_refund cpr join cm_commission_orders cmo ON (cmo.shopping_cart_id = cpr.order_id)
						where cpr.is_paid IS  NULL
						AND cpr.order_id NOT IN  (select cpd.order_id  from cm_commission_payouts cp inner join cm_commission_payout_details cpd on (cp.commission_payout_id = cpd.commission_payout_id) where cp.value < 1)";

        $smt = $this->db->prepare($query);
        $smt->execute();
        $refund = $smt->fetchAll(PDO::FETCH_ASSOC);

        $details = array();
        foreach($refund as $r){
            $commission = $r['amount'] * -1;

            $detail = array('sponsor_id' => $r['sponsor_id'],
                'business_center_id' => $r['bc_id'],
                'order_id' => $r['order_id'],
                'value' => $commission,
                'level' => 0,
                'commission_order_id' => $r['commission_order_id'],
                'payout_type' => 2,
                'commission' => $commission,
                'percentage' => 1);
            //print_rx($detail);
            $details[] = $detail;
        }
        $this->doCommissionPayout($details);
    }
	
	private function getPeriodRankCount($bcid,$period_id){		
		$query = "Select * from commission_period_ranks where member_id=:bcid and commission_period_id=:period_id";
		
		$this->db->query($query);
		$this->db->bind(':bcid',$bcid);	
		$this->db->bind(':period_id',$period_id);
		
		$result = $this->db->resultset();
		return $this->db->rowCount();
	}
	
	private function newPeriodRank($bcid,$period_id,$rank_id){
		$query = "insert into commission_period_ranks(member_id,commission_period_id,commission_rank_id)
							values(:bcid,:period_id,:rank_id)";
		
		$this->db->query($query);
		$this->db->bind(':rank_id',$rank_id);	
		$this->db->bind(':bcid',$bcid);
		$this->db->bind(':period_id',$period_id);
		$database->execute();
	}
	
	private function updatePeriodRank($bcid,$period_id,$rank_id){
		$query = "update commission_period_ranks set commission_rank_id=:rank_id 
					where member_id=:bcid and commission_period_id=:period_id";
		
		$this->db->query($query);
		$this->db->bind(':rank_id',$rank_id);	
		$this->db->bind(':bcid',$bcid);
		$this->db->bind(':period_id',$period_id);
		$database->execute();
	}
	
	private function getCommissionTypeName($type){
		$query = "SELECT name FROM cm_commission_period_types WHERE commission_period_type_id=:type";
		$smt = $this->db->prepare($query);
		$smt->bindParam('type',$type);
		$smt->execute();
		$result = $smt->fetch(PDO::FETCH_ASSOC);							
		return $result['name'];
	}

    public function generateReportAlt($type=null,$period_id=null){

        $start = date('YFj', strtotime($this->start_date));
        $end = date('YFj', strtotime($this->end_date));

        $ctype = str_replace(' ','',$this->getCommissionTypeName($type));

        $filename = strtolower($ctype."_".$start."-".$end."_payouts.csv");
        $filename2 = strtolower($ctype."_".$start."-".$end."_payout_details.csv");


        $payouts = $this->getPayouts();


        $payout_details = $this->getPayoutDetails();

        $data_count = count($payouts);
        $totals = 0;
        if($data_count > 0){
            foreach($payouts as $row){
                $totals += $row['total_payout'];
            }
        }

        if (!empty($payouts) || !empty($payout_details)) {
            if ($type) {
                $this->exportToCSVAlt($payouts, $filename, "Download Commission Payouts ".$this->getCommissionTypeName($type)." (".$start."-".$end.")");
                $this->exportToCSVAlt($payout_details, $filename2, "Download Commission Payout Details ".$this->getCommissionTypeName($type)." (".$start."-".$end.")");
            } else {
                $this->exportToCSVAlt($payouts, $filename, "Download Commission Payouts (".$start."-".$end.")");
                $this->exportToCSVAlt($payout_details, $filename2, "Download Commission Payout Details (".$start."-".$end.")");
            }

            print '<div id="total" style="padding:15px;margin-top:20px;display:block;background-color:brown;color:white;font-weight:bold;text-align:left !important;">Total Commission Payout : $ '.$totals.'</div>';
        }
        else
            print "NO COMMISSION REPORT!";
    }
	
    public function getRefundablePayouts($user_id){

        
       // $query = "select cp.user_id,cp.commission_payout_id,cpd.order_id,cpd.value as tot_value,cpd.amount,p.sku,p.name,o.transactiondate as order_date
                // from cm_commission_payout_details cpd
                // inner join cm_commission_payouts cp on (cp.commission_payout_id = cpd.commission_payout_id)
                // inner join transactions o on (o.id = cpd.order_id)
                // inner join shoppingcart_products p on (o.itemid = p.id)
                // inner join users u on (cp.user_id=u.id)
                // inner join cm_commission_periods per on (cp.commission_period_id = per.commission_period_id)
                // where  per.locked =1  AND cp.user_id = :user_id
                // AND cp.commission_payout_id NOT IN (SELECT commission_payout_id FROM cm_commission_payout_refund) ";
       

        $query = "SELECT 
					(p.commission_value * commission_percentage) AS tot_value
					,co.shopping_cart_id as order_id
					,sp.transactiondate as purchasedate
					,p.sku,cpt.name commission_type
					,p.name as product_name
					,co.sold_to_id as user_id
					,(
						select 
							count(cpd.order_id) as count_t 
						from cm_commission_payout_details as cpd
						inner join cm_commission_payouts as cp on (cp.commission_payout_id = cpd.commission_payout_id)
						inner join cm_commission_periods as cpp on (cpp.commission_period_id = cp.commission_period_id) 
						where (cpp.locked = 1) and (cpd.order_id = co.shopping_cart_id)
					) as count_t
                    FROM cm_commission_orders as co
                    INNER JOIN transactions as sp ON (co.shopping_cart_id=sp.id)
                    INNER JOIN shoppingcart_products as p ON (sp.itemid=p.id)
                    INNER JOIN cm_commission_payout_types as cpt ON (co.commission_type=cpt.commission_payout_type_id)
                    WHERE (co.sold_to_id = :user_id) AND
                          (co.shopping_cart_id)
                    NOT IN (select cm_commission_payout_refund.order_id FROM cm_commission_payout_refund)";

        $smt = $this->db->prepare($query);
        $smt->bindParam('user_id',$user_id);
        $smt->execute();
        $payouts = $smt->fetchAll(PDO::FETCH_ASSOC);

        return $payouts;
    }

    private function getPayoutsPool(){
        $query = "SELECT m.lname as first_name, m.fname as last_name, m.business as bussiness_name, m.site as user_name, m.id as member_id, ROUND( SUM( cp.value ) , 2 ) AS total_payout,cp.level as total_shares,
                  (select count(commission_payout_id) from cm_commission_payout_details WHERE commission_payout_id = cp.commission_payout_id) as total_orders
					FROM users m
					JOIN cm_commission_payouts cp
					ON ( m.id = cp.user_id )
					WHERE cp.commission_period_id =:period_id
					GROUP BY cp.user_id
					ORDER BY total_payout DESC";

        $smt = $this->db->prepare($query);
        $smt->bindParam('period_id',$this->period_id);
        $smt->execute();
        $result = $smt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    private function getPayoutDetailsOverride(){

        $query = "SELECT CONCAT(s.fname,' ',s.lname, ' ' , co.seller_id) as seller,
                CONCAT(st.fname,' ',st.lname, ' ' , co.sold_to_id) as sold_to,
                CONCAT(pu.fname,' ',pu.lname, ' ' , co.passup_sponsor_id) as passup_sponsor,
                cp.value AS payout_detail, cpt.description AS commission_type,
                cpd.order_id,p.sku,cpd.value AS volume_calculated_from, cpd.percent AS percent_payout,co.sales_count
                FROM cm_commission_payouts cp
                JOIN cm_commission_payout_types cpt
                USING ( commission_payout_type_id )
                JOIN cm_commission_payout_details cpd
                USING ( commission_payout_id )
                INNER JOIN cm_commission_orders co ON (cpd.commission_order_id = co.id)
                INNER JOIN transactions sp ON (cpd.order_id = sp.id)
                INNER JOIN shoppingcart_products p ON (sp.itemid = p.id)
                INNER JOIN users s ON (co.seller_id =s.id)
                INNER JOIN users st ON (co.sold_to_id =st.id)
                INNER JOIN users pu ON (co.passup_sponsor_id = pu.id)
                WHERE cp.commission_period_id =:period_id ";


        $smt = $this->db->prepare($query);
        $smt->bindParam('period_id',$this->period_id);
        $smt->execute();
        $result = $smt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
	
	private function exportToCSV($data, $filename, $details) {
		$path = dirname(__FILE__);
		$reports_path = $path."/reports/";
		$fp = fopen($reports_path.$filename, 'w');
		$data_count = count($data);
		
		if ($data_count > 0) {
			
			fputcsv($fp, array_keys($data[0]));
			foreach ($data as $row) {
				fputcsv($fp, $row, ',', '"');
			}
			fclose($fp);
			$report_link = strtolower("https://".$_SERVER['HTTP_HOST']."/commissions/reports/".$filename);
			 print "<br><b><a style='margin-bottom:10px !important;' class='btn btn-success' href=".$report_link."><span class='glyphicon glyphicon-th-list' aria-hidden='true'></span>&nbsp;&nbsp;".$details."</a></b>";
		} 
		else {
			
			print "NO RESULTS!";
		}
	}

    private function exportToCSVAlt($data, $filename, $details){

        $path = dirname(__FILE__);
        $reports_path = $path."/reports/";
        //echo $reports_path;die();
        $fp = fopen($reports_path.$filename, 'w');

        $data_count = count($data);

        if($data_count > 0){


            fputcsv($fp, array_keys($data[0]));

            foreach($data as $row){
                fputcsv($fp, $row, ',', '"');
            }

            fclose($fp);

            $report_link = strtolower("https://".$_SERVER['HTTP_HOST']."/commissions/reports/".$filename);
            //$report_link = strtolower("https://peaks-office.com/commissions/reports/".$filename);
            //print $report_link;
            print "<br><b><a href=".$report_link.">".$details."</a></b>";
        }
        else{
            print "NO RESULTS!";
        }
    }

    public function lockCommissionPeriod($period_id){
    	
        $query = "UPDATE cm_commission_periods SET locked=1 WHERE commission_period_id = " . $period_id;
        $smt = $this->db->prepare($query);
        $success = $smt->execute();
        
        if($success){
            echo "<br/>commission period locked.";
        }else{
            echo "<br/>commission period not locked.";
        }
    }

    public function getAffilliateEarnings($user_id){
       //$current_points = $result['lifetime_points'];
        $life_time_earnings = $this->getLifetimeEarnings($user_id,1);
        $pending_earnings = $this->getPendingEarnings($user_id,$life_time_earnings);

        $detail = array('life_earnings' => number_format($life_time_earnings,2) ,
            'pending_earnings' => number_format($pending_earnings,2));

        return $detail;
    }

    private function getLifetimeEarnings($user_id,$group_id){

        /*
        $query = "SELECT SUM(scp.commission_value) as total_points
                FROM shoppingcart_purchases sc JOIN shoppingcart_products scp on (sc.productid=scp.id)
                JOIN users d on sc.userid = d.id
                JOIN users d2 on d.sponsorid = d2.id
                WHERE d2.active = 'YES' AND d2.id=:user_id";
        */
        /*
        $query = "SELECT SUM(p.commission_value * commission_percentage ) AS total_points
                FROM cm_commission_orders co
                INNER JOIN users u ON (co.passup_sponsor_id = u.id )
                INNER JOIN transactions sp ON (co.shopping_cart_id=sp.id)
                INNER JOIN shoppingcart_products p ON (sp.itemid=p.id)
                WHERE u.id = :user_id AND u.active='YES' "; */

        $query = "SELECT SUM(p.commission_value * commission_percentage ) AS total_points
                FROM cm_commission_orders co
                INNER JOIN users u ON (co.passup_sponsor_id = u.id )
                INNER JOIN transactions sp ON (co.shopping_cart_id=sp.id)
                INNER JOIN shoppingcart_products p ON (sp.itemid=p.id)
                WHERE u.id = :user_id AND u.active='YES'
                AND co.shopping_cart_id IN (
                SELECT cpd.order_id as shopping_cart_id FROM cm_commission_payout_details cpd
                    INNER JOIN cm_commission_payouts cp ON (cpd.commission_payout_id=cp.commission_payout_id)
                    INNER JOIN cm_commission_periods cmp ON (cmp.commission_period_id = cp.commission_period_id)
                    WHERE cmp.locked = 1
                )";

        $smt = $this->db->prepare($query);
        $smt->bindParam('user_id',$user_id);
        $smt->execute();
        $result = $smt->fetch(PDO::FETCH_ASSOC);

        $total =  $result['total_points'];

        return $total;
    }

    private function getPendingEarnings($user_id,$life_time_earning){
        /*
        $query = "select SUM(value) as total_value from cm_commission_payouts
                WHERE user_id =:user_id  ";
        */

        $query = "SELECT SUM(p.commission_value * commission_percentage ) AS total_points
                FROM cm_commission_orders co
                INNER JOIN users u ON (co.passup_sponsor_id = u.id )
                INNER JOIN transactions sp ON (co.shopping_cart_id=sp.id)
                INNER JOIN shoppingcart_products p ON (sp.itemid=p.id)
                WHERE u.id = :user_id AND u.active='YES'
                AND co.shopping_cart_id NOT IN (
                SELECT cpd.order_id as shopping_cart_id FROM cm_commission_payout_details cpd
                    INNER JOIN cm_commission_payouts cp ON (cpd.commission_payout_id=cp.commission_payout_id)
                    INNER JOIN cm_commission_periods cmp ON (cmp.commission_period_id = cp.commission_period_id)
                    WHERE cmp.locked = 1
                )";

        $smt = $this->db->prepare($query);
        $smt->bindParam('user_id',$user_id);
        $smt->execute();
        $result = $smt->fetch(PDO::FETCH_ASSOC);

        $total = $result['total_points'];

        return $total;
    }

    public function getPendingReferrals($user_id){
        /*
        $query = "SELECT u.fname,u.lname,date(sc.purchasedate) as date_purchased,scp.name as product_name,scp.sku
                FROM shoppingcart_purchases sc
                INNER JOIN users u on (sc.userid=u.id)
                INNER JOIN shoppingcart_products scp ON (sc.productid=scp.id)
                WHERE u.sponsorid=:user_id  and sc.id NOT IN(select pd.order_id as id from cm_commission_payout_details pd
                JOIN cm_commission_payouts p using(commission_payout_id)
                WHERE p.user_id =:user_id and p.commission_payout_type_id = :commission_type)";
        */

        $query = "SELECT (p.commission_value * commission_percentage) AS total_points,
                    CONCAT(seller.id,' ',seller.fname,' ',seller.lname) seller,
                    CONCAT(sold_to.id,' ',sold_to.fname,' ',sold_to.lname) sold_to ,
                    sp.transactiondate as purchasedate,p.sku,cpt.name commission_type,p.name as product_name,co.sales_count
                    FROM cm_commission_orders co
                    INNER JOIN users pass_up ON (co.passup_sponsor_id = pass_up.id )
                    INNER JOIN users seller ON (co.seller_id =seller.id )
                    INNER JOIN users sold_to ON (co.sold_to_id = sold_to.id )
                    INNER JOIN transactions sp ON (co.shopping_cart_id=sp.id)
                    INNER JOIN shoppingcart_products p ON (sp.itemid=p.id)
                    INNER JOIN cm_commission_payout_types cpt ON (co.commission_type=cpt.commission_payout_type_id)
                    WHERE pass_up.id = :user_id AND pass_up.active='YES'
                    AND co.shopping_cart_id
                    NOT IN (SELECT cpd.order_id as shopping_cart_id FROM cm_commission_payout_details cpd
                    INNER JOIN cm_commission_payouts cp ON (cpd.commission_payout_id=cp.commission_payout_id)
                    INNER JOIN cm_commission_periods cmp ON (cmp.commission_period_id = cp.commission_period_id)
                    WHERE cmp.locked = 1)";

        $smt = $this->db->prepare($query);
        $smt->bindParam('user_id',$user_id);
        $smt->execute();
        $result = $smt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getHistoricalReferrals($user_id,$period_id){

        $query = "select pd.order_id,u.lname,u.fname,date(o.transactiondate) as date_purchased, scp.name as product_name,pd.amount,cmo.sales_count
                    from cm_commission_payout_details pd
                    INNER JOIN cm_commission_payouts p using(commission_payout_id)
                    INNER JOIN transactions o ON (pd.order_id = o.id)
                    INNER JOIN users u on (o.userid = u.id)
                    INNER JOIN shoppingcart_products scp ON (o.itemid = scp.id)
                    INNER JOIN cm_commission_periods pp ON (pp.commission_period_id = p.commission_period_id)
                    INNER JOIN cm_commission_orders cmo ON (cmo.shopping_cart_id = o.id)
                    WHERE p.user_id =:user_id
                    and p.commission_period_id =:period_id AND pp.locked=1 AND cmo.passup_sponsor_id=:user_id";

        $smt = $this->db->prepare($query);
        $smt->bindParam('user_id',$user_id);
        $smt->bindParam('period_id',$period_id);
        $smt->execute();
        $result = $smt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getAffiliatePassUp($user_id){

        $query = "CALL sp_generatePassupReport(:user_id);";

        $smt = $this->db->prepare($query);
        $smt->bindParam(':user_id', $user_id);
        $smt->execute();
        $result = $smt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getProductsSalesCount($user_id){
        $query ="SELECT * from shoppingcart_products";
        $smt = $this->db->prepare($query);
        $smt->execute();
        $results = $smt->fetchAll(PDO::FETCH_ASSOC);

        $sales = array();

        foreach($results as $result){
            $sales[]= array('id'=>$result['id'],
                            'sku'=>$result['sku'],
                            'name'=>$result['name'],
                            'sales_count'=> $this->getSalesCount($result['id'],$user_id)
                                );
        }

        return $sales;
    }
	
	public function getProductsSalesCountReport($user_id){
        $query ="SELECT * from shoppingcart_products";
        $smt = $this->db->prepare($query);
        $smt->execute();
        $results = $smt->fetchAll(PDO::FETCH_ASSOC);

        $sales = array();

        foreach($results as $result){
            $sales[]= array('id'=>$result['id'],
                            'sku'=>$result['sku'],
                            'name'=>$result['name'],
                            'sales_count'=> $this->getSalesCountReport($result['id'],$user_id)
                                );
        }

        return $sales;
    }

    private function getSalesCount($product_id,$user_id){

    $query = "SELECT COUNT(co.id) as total_count_sale
                FROM cm_commission_orders co
                INNER JOIN transactions sp ON (sp.id=co.shopping_cart_id)
                INNER JOIN shoppingcart_products p ON (sp.itemid = p.id)
                WHERE co.passup_sponsor_id = :user_id AND p.id = :product_id AND co.not_included <> 1;   ";

        $return = 0;
        $stmt =$this->db->prepare($query);
        $stmt->bindParam('user_id',$user_id);
        $stmt->bindParam('product_id',$product_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
		
        $return = $result['total_count_sale'];
		
        if($product_id == '32' || $product_id == '33' || $product_id == '34' || $product_id == '35' || $product_id == '36' || $product_id == '37' || $product_id == '40' || $product_id == '41' || $product_id == '42' || $product_id == '43'|| $product_id == '44'|| $product_id == '45'|| $product_id == '46'|| $product_id == '47'|| $product_id == '48' || $product_id == '49' ){
            if($user_id == '453'){
                $return +=0;
            }
        }


        if($user_id == '3' || $user_id=='1' || $user_id=='227'  || $user_id=='228' || $user_id=='229' || $user_id=='230' || $user_id=='231' || $user_id=='14349' ){
            $return += 13;
        }


        return $return;
    }
	
	private function getSalesCountReport($product_id,$user_id){

        $query = "SELECT sales_count as total_count_sale
                FROM cm_commission_orders co
                INNER JOIN transactions sp ON (sp.id=co.shopping_cart_id)
                INNER JOIN shoppingcart_products p ON (sp.itemid = p.id)
                WHERE co.passup_sponsor_id = :user_id AND p.id = :product_id AND co.not_included <> 1;   ";

        $return = 0;
        $stmt =$this->db->prepare($query);
        $stmt->bindParam('user_id',$user_id);
        $stmt->bindParam('product_id',$product_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		
		
        $return = $result['total_count_sale'];
		
		if($result['total_count_sale'] == null){
			return 'none';
		}

        if($product_id == '32' || $product_id == '33' || $product_id == '34' || $product_id == '35' || $product_id == '36' || $product_id == '37' || $product_id == '40' || $product_id == '41' || $product_id == '42' || $product_id == '43'|| $product_id == '44'|| $product_id == '45'|| $product_id == '46'|| $product_id == '47'|| $product_id == '48' || $product_id == '49' ){
            if($user_id == '453'){
                $return +=0;
            }
        }


        if($user_id == '3' || $user_id=='1' || $user_id=='227'  || $user_id=='228' || $user_id=='229' || $user_id=='230' || $user_id=='231' || $user_id=='14349' ){
            $return += 13;
        }

        return $return;
    }	
}

?>