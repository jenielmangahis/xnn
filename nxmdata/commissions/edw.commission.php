<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/commissions/commissions.class.php');

class EDW_Commission extends Commission {
	private $debug;
	private $autoqualified;
	
	public function __construct($test=false){		
		parent::__construct($test);
	}
	
	public function setDebug() {
		$this->debug = true;
	}

    public function retailCommission() {
        // Insert records to payout and payout details tables the result of 
        // the calculation of retail commission.
        // This is then became the source of report for retails commission.
        $user = 0; // system.
        $key = 'RETAIL-COMMISSION';

        try {
            $query = "CALL sp_calculateCommision(
                              :p_logged_on_user
                            , :p_commission_type
                            , :p_commission_period_id
                        );";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':p_logged_on_user', $user);
            $stmt->bindParam(':p_commission_type', $key);
            $stmt->bindParam(':p_commission_period_id', $this->period_id);
            $stmt->execute();
        } catch(PDOException $ex) {
            echo '{"status":"error", "details":'.$ex->getMessage().'}';
        }
    } // retailCommission

    public function retailPoolCommission() {
        $user = 0; // system.
        $key = 'RETAIL-POOL-COMMISSION';
        
        try {
            $query = "CALL sp_calculateCommision(
                              :p_logged_on_user
                            , :p_commission_type
                            , :p_commission_period_id
                        );";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':p_logged_on_user', $user);
            $stmt->bindParam(':p_commission_type', $key);
            $stmt->bindParam(':p_commission_period_id', $this->period_id);
            $stmt->execute();
        } catch(PDOException $ex) {
            echo '{"status":"error", "details":'.$ex->getMessage().'}';
        }
    } // retailPoolCommission()
    
    public function checkMatchCommission() {
        $user = 0; // system.
        $key = 'CHECKMATCH-COMMISSION';

        try {
            $query = "CALL sp_calculateCommision(
                              :p_logged_on_user
                            , :p_commission_type
                            , :p_commission_period_id
                        );";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':p_logged_on_user', $user);
            $stmt->bindParam(':p_commission_type', $key);
            $stmt->bindParam(':p_commission_period_id', $this->period_id);
            $stmt->execute();
        } catch(PDOException $ex) {
            echo '{"status":"error", "details":'.$ex->getMessage().'}';
        }
    } // checkMatchCommission
    
    public function codedCommission() {
        $user = 0; // system.
        $key = 'CODED-COMMISSION';

        try {
            $query = "CALL sp_calculateCommision(
                              :p_logged_on_user
                            , :p_commission_type
                            , :p_commission_period_id
                        );";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':p_logged_on_user', $user);
            $stmt->bindParam(':p_commission_type', $key);
            $stmt->bindParam(':p_commission_period_id', $this->period_id);
            $stmt->execute();
        } catch(PDOException $ex) {
            echo '{"status":"error", "details":'.$ex->getMessage().'}';
        }
    } // codedCommission    
    
    public function barryCommission() {
        $user = 0; // system.
        $key = 'MONTHLY-BARRY';
        
        try {
            $query = "CALL sp_calculateCommision(
                              :p_logged_on_user
                            , :p_commission_type
                            , :p_commission_period_id
                        );";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':p_logged_on_user', $user);
            $stmt->bindParam(':p_commission_type', $key);
            $stmt->bindParam(':p_commission_period_id', $this->period_id);
            $stmt->execute();
        } catch(PDOException $ex) {
            echo '{"status":"error", "details":'.$ex->getMessage().'}';
        }
    } // barryCommission

    public function processCommission($transaction_id){
        $logon_user = 0;
        $result = null;
        
        try {
            $query = "CALL sp_calculatePassup(
                         :p_logged_on_user -- active user.
                        ,:p_transaction_id -- id(primary key) of the transaction.
                      );";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':p_logged_on_user', $logon_user);
            $stmt->bindParam(':p_transaction_id', $transaction_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch (PDOException $ex) {
            echo '{"status":"error", "details":'.$ex->getMessage().'}';
        }
    } // processCommission

    public function logPropay($payout_id, $account_number, $amount, $requestXml, $responseXml) {
        
        try {

            $sql = "INSERT INTO propay_log(
                             commission_payout_id
                            ,amount
                            ,account_number
                            ,request_xml
                            ,response_xml
                            ,created)
                      VALUES(
                             :payout_id
                            ,:amount
                            ,:account_number
                            ,:request
                            ,:response
                            ,now());";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam('payout_id', $payout_id);
            $stmt->bindParam('account_number', $account_number);
            $stmt->bindParam('amount', $amount);
            $stmt->bindParam('request', $requestXml);
            $stmt->bindParam('response', $responseXml);
            $stmt->execute();
        } catch(PDOException $e) {
            echo '{"status":"error", "details":' . $ex->getMessage() . '}';
        }
    } /* logPropay */

    public function getPayout($payoutId) {
        $sql = "SELECT 
                     payout.*
                    ,pi.account_number
                    ,u.fname
                    ,u.lname
                FROM cm_commission_payouts  AS payout 
                LEFT JOIN propay_info       AS pi   ON pi.user_id = payout.user_id 
                LEFT JOIN users             AS u    ON u.id = payout.user_id
                WHERE (commission_payout_id = :payout_id) AND 
                      (COALESCE(is_paid, 0) = 0)";
        $stmt =$this->db->prepare($sql);
        $stmt->bindParam("payout_id", $payoutId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    } /* getPayout */

    public function getOrderReports($from,$to) {
        
		$query = "SELECT
                CONCAT(seller.id,' ',seller.fname,' ',seller.lname) sponsor_name,
                CONCAT(u.id,' ',u.fname,' ',u.lname) user_name,
                p.sku,p.name as product_name, date(trans.transactiondate) as purchasedate,p.commission_value,
                IF(uu.user_url IS NOT NULL, uu.user_url, '') AS user_url,trans.id as transaction_id
                FROM transactions trans
                INNER JOIN users u ON (trans.userid = u.id)
                INNER JOIN users seller ON (u.sponsorid = seller.id)
                INNER JOIN shoppingcart_products p ON (trans.itemid = p.id)
                LEFT JOIN user_url uu ON (uu.product_id = p.id AND uu.user_id=trans.userid AND date(trans.transactiondate) =date(uu.timestamp) )
                WHERE date(trans.transactiondate) between :start_date AND :end_date
                AND status='Approved' AND trans.itemid IN (29,30,31,32,33,34,35,36,37,40,41,42,43,44,45,46,47,48,49)";

        $stmt =$this->db->prepare($query);
        $stmt->bindParam('start_date',$from);
        $stmt->bindParam('end_date',$to);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $orders;
    }

	public function directCommission() {
		/*
		$query = "SELECT sp.id as transaction_id,sp.userid as user_id,p.commission_value as total_volume,sp.productid as product_id,
                    u.sponsorid
                    FROM shoppingcart_purchases sp
                    INNER JOIN shoppingcart_products p ON (sp.productid = p.id)
                    INNER JOIN users u ON (sp.userid = u.id)
                    INNER JOIN users u2 ON (u.sponsorid = u2.id)
                    WHERE  date(sp.purchasedate) between :start_date and :end_date ";
		*/

        $query = "SELECT co.shopping_cart_id transaction_id,co.sold_to_id as user_id,co.passup_sponsor_id as sponsor_id,
                p.commission_value as total_volume,p.id as product_id,co.commission_percentage,co.id as commission_transaction_id
                FROM cm_commission_orders co
                INNER JOIN transactions sp on (co.shopping_cart_id = sp.id)
                INNER JOIN shoppingcart_products p on (sp.itemid = p.id)
                WHERE co.commission_type ='2' AND date(sp.transactiondate) between :start_date AND :end_date
                AND co.shopping_cart_id NOT IN (SELECT transaction_id from cm_commission_payout_refund where in_payout=0)";

		$stmt =$this->db->prepare($query);
		
		$stmt->bindParam('start_date',$this->start_date);
		$stmt->bindParam('end_date',$this->end_date);
		$stmt->execute();
		$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);	

        //var_dump($orders);die();

		if($this->debug)
			print_rx($orders);								
		
		$details = array();

		foreach($orders as $o){

            $commission = round($o['total_volume'] * $o['commission_percentage'], 2);

            $detail = array('sponsor_id' => $o['sponsor_id'],
                            'business_center_id' => $o['user_id'],
                            'transaction_id' => $o['transaction_id'],
                            'level' => 1,
                            'payout_type' => DIRECT_COMMISSION_PAYOUT_ID,
                            'value' => $commission,
                            'commission' => $o['total_volume'],
                            'percentage' => $o['commission_percentage'],
                            'commission_transaction_id' => $o['commission_transaction_id'],
                            'product' => $o['product_id']);
            if($this->debug)
                print_rx($detail);

            $details[] = $detail;

		}

        //var_dump($details);die();
		if($this->debug)
			print_rx($details);
		
		if(!$this->debug){
			$this->doCommissionPayout($details);
			$this->get_refund();
		}
	}

    public function passUpCommission() {

        $query = "SELECT co.shopping_cart_id transaction_id,co.sold_to_id as user_id,co.passup_sponsor_id as sponsor_id,
                p.commission_value as total_volume,p.id as product_id,co.commission_percentage,co.id as commission_transaction_id
                FROM cm_commission_orders co
                INNER JOIN transactions sp on (co.shopping_cart_id = sp.id)
                INNER JOIN shoppingcart_products p on (sp.itemid = p.id)
                WHERE co.commission_type ='3' AND date(sp.transactiondate) between :start_date AND :end_date ";


        $stmt =$this->db->prepare($query);
        $stmt->bindParam('start_date',$this->start_date);
        $stmt->bindParam('end_date',$this->end_date);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


        if($this->debug)
            print_rx($orders);

        $details = array();
        foreach($orders as $o){

            $commission = round( $o['total_volume'] * $o['commission_percentage'], 2);

            $detail = array('sponsor_id' => $o['sponsor_id'],
                'business_center_id' => $o['user_id'],
                'transaction_id' => $o['transaction_id'],
                'level' => 1,
                'payout_type' => 3,
                'value' => $commission,
                'commission' => $o['total_volume'],
                'percentage' => $o['commission_percentage'],
                'commission_transaction_id' => $o['commission_transaction_id'],
                'product' => $o['product_id']);
            if($this->debug)
                print_rx($detail);

            $details[] = $detail;

        }

        if ($this->debug)
            print_rx($details);

        if (!$this->debug) {
            $this->doCommissionPayout($details);
            $this->get_refund();
        }
    }
	
    private function getSalesCount($user_id, $product_id){
		$retval = 0;
		
		try {
			$query = "SELECT sp_get_sales_count(
							 :p_user_id
							,:p_product_id
						) AS sales_count;";
			$stmt = $this->db->prepare($query);
			$stmt->bindParam(':p_user_id',$user_id);
			$stmt->bindParam(':p_product_id',$product_id);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			$retval = $result['sales_count'];	
		}
		catch (PDOException $ex) {
			echo '{"status":"error", "details":'.$ex->getMessage().'}';
		}
		
		return $retval;
    } // getSalesCount

    public function checkUserExist($user_id){
        $query = "SELECT count(id) as user_count FROM users WHERE id=:user_id";
        $stmt =$this->db->prepare($query);
        $stmt->bindParam('user_id',$user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($result['user_count'] > 0);
    }

    private function getProductSponsor($user_id,$product_id){
        $product_sponsor_id = "-1";

        $query = "SELECT product_sponsor_id FROM cm_product_sponsors WHERE product_id=:product_id AND user_id=:user_id";
        $stmt =$this->db->prepare($query);
        $stmt->bindParam('user_id',$user_id);
        $stmt->bindParam('product_id',$product_id);
        $stmt->execute();

        if( $result = $stmt->fetch(PDO::FETCH_ASSOC)){
            $product_sponsor_id = $result['product_sponsor_id'];
        }
        return $product_sponsor_id;
    }

    private function getEligibleProductSponsor($user_id,$product_id,$phase){
        $query = "SELECT sp_get_qualified(
					:p_user_id -- p_user_id INT(11)
					,:p_product_id --  p_product_id INT(11)
					,:p_phase -- p_phase ENUM('PHASE_1', 'PHASE_2')
					) as is_eligble";

        $stmt =$this->db->prepare($query);
        $stmt->bindParam('p_user_id',$user_id);
        $stmt->bindParam('p_product_id',$product_id);
        $stmt->bindParam('p_phase',$phase);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
		
        return  $result['is_eligible'];
    }

	private function getSponsorDetails($id){
		$query = "Select m.group_id,m.sponsorid as sponsor_id,m.active from users m where m.id=:id";
		
		$stmt =$this->db->prepare($query);
		$stmt->bindParam('id',$id);				
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$detail['sponsor'] = $result['sponsor_id'];
		$detail['group_id'] = $result['group_id'];
		$detail['active'] = $result['active'];
		return $detail;
	}

    private function getSponsorPercentage($id){
        $query = "Select co.percentage from cm_manual_commission_override co where co.user_id=:id";

        $stmt =$this->db->prepare($query);
        $stmt->bindParam('id',$id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $value = '20';
        if(!empty($result['percentage'])){
            $value = $result['percentage'];
        }

        return $value /  100;

    }

	private function getTotalShares($orders){
		$shares = 0;
		foreach($orders as $o){
			$shares += floor($o['sales_count'] / 20);			
		}
		return $shares;
	}
	
	private function getTotalMonthlyCV(){
		$query = "Select sum(p.volume) as pv 
					from cm_orders o join order_statuses os using(transaction_id)
					join cm_products p on p.sku=o.product_sku
					where date(os.timestamp) between :start_date and :end_date
					and os.order_status_type_id= :commissionable 					
					and transaction_id not in(Select transaction_id from cm_order_statuses where order_status_type_id=:refund_status)";

        $comm_status_id = COMMISSIONABLE_STATUS_ID;
        $refund_status_id = REFUND_STATUS_ID;

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date',$this->start_date);
        $stmt->bindParam(':end_date',$this->end_date);
        $stmt->bindParam(':commissionable',$comm_status_id);
        $stmt->bindParam(':refund_status',$refund_status_id);
		$stmt->execute();
		$result =$stmt->fetch(PDO::FETCH_ASSOC);

		$value = $result['pv'];
										
		return $value;								
	}

    public function productExists($sku)
    {
        $sql = "SELECT * FROM cm_products p WHERE p.sku=:sku;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':sku' => $sku));
        return !!$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function orderExists($transaction_id)
    {
        $sql = "SELECT * FROM cm_orders o WHERE o.transaction_id=:transaction_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':transaction_id' => $transaction_id));
        return !!$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function userExists($user_id) {
        $sql = "SELECT * FROM users u WHERE u.id=:user_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':user_id' => $user_id));
        return !!$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAvailablePoints($user_id){

        $query = "SELECT getLifeTimePoints(:user_id) AS lifetime_points, getTotalRedeemedPoints(:user_id) AS redeemed_points";
        $smt = $this->db->prepare($query);
        $smt->bindParam('user_id',$user_id);
        $smt->execute();
        $result = $smt->fetch(PDO::FETCH_ASSOC);

        $current_points = $result['lifetime_points'] - $result['redeemed_points'];
        $available_points =  $current_points - $_POST['points'];

        return $available_points;
    }

    public function getChildren($user_id){
        $sql = "SELECT u.*,CONCAT(s.fname,' ',s.lname) as sponsor_name
        FROM users u
        INNER JOIN users s ON (u.sponsorid = s.id)
        WHERE u.sponsorid=:user_id and u.levelid=3";


        $stmt =$this->db->prepare($sql);
        $stmt->bindParam("user_id", $user_id);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $details = array();
        foreach($results as $r){
            $child_count = $this->getChildCount($r['id']);
            $branch ="false";

            if($child_count > 0) $branch="true";

            $detail = array('id' => $r['id'],
                'fname' => $r['fname'],
                'lname' =>$r['lname'],
                'levelid' => $r['levelid'],
                'branch' => $branch,
                'city' => $r['city'],
                'country' => $r['country'],
                'active' => $r['active'],
                'sponsor_name' => $r['sponsor_name']);

            $details[] = $detail;
        }

        return $details;
    }

    private function getChildCount($user_id) {
        $sql = "SELECT count(id) as m_count from users where sponsorid=:user_id";
        $stmt =$this->db->prepare($sql);
        $stmt->bindParam("user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['m_count'];
    }
}

?>