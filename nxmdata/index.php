<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('UTC');

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

require_once("Slim/Slim.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/includes/db.config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/includes/DB.class.new.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/commissions/propay.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/commissions/payap.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/commissions/elm.commission.php");

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

//******************************************************************************************
// BEGIN: ELM related functions
//******************************************************************************************

// Get Rank by Id
$app->get('/ranks/:id', function($id) {

    try {
        $sql = "SELECT * FROM ranks WHERE id = :id LIMIT 1";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

// Get Rank by User Id
$app->get('/user_rank/:userId', function($userId) {

    try {
        $sql = "SELECT r.* FROM ranks r INNER JOIN users u ON u.rank_id = r.id WHERE u.id = :userId LIMIT 1";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

// Get all date periods by Commission Type
$app->get('/dateperiodsbytype/:type', function($type){
    try {
        $elm = new ELM_Commission();
        $result = $elm->getDatePeriodsByType($type);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

});

// Get Historical Commission
$app->post('/historicalcommission', function() use ($app){
    try {
        $data = array('user_id'=>$_POST['user_id'],'from'=>$_POST['from'],'to'=>$_POST['to'],'type'=>$_POST['type']);
        $cr = new ELM_Commission();
        $result = $cr->getHistoricalCommissions($data);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

//******************************************************************************************
// END: ELM related functions
//******************************************************************************************

// ******************************************************************************************
// BEGIN: Payap related functions.
// ******************************************************************************************

$app->get('/payap/user/:user_id', function($user_id) {

    $obj = new Payap($user_id);
    $result = $obj->getUser();

    echo $result;
});

$app->get('/payap/user/send_money/:user_id/:cid/:toType/:toPhone/:amount/:memo', function($user_id, $cid, $toType, $toPhone, $amount, $memo) {

    $obj = new Payap($user_id);
    $result = $obj->sendMoney($cid, $toType, $toPhone, $amount, $memo);

    echo $result;
});

$app->get('/payap/user/add_bankcard/:user_id/:cardname/:card_number/:card_expiration/:card_cvv', function($user_id, $cardname,$card_number,$card_expiration,$card_cvv) {
    
    $obj = new Payap($user_id);
    $result = $obj->addBankCard($cardname, $card_number, $card_expiration, $card_cvv);
    
    echo $result;
});

$app->get('/payap/country_codes', function() {

    $obj = new Payap();
    $result = $obj->getCurrencyCodes();
    
    echo $result;
});

// ******************************************************************************************
// END: Payap related functions.
// ******************************************************************************************

$app->get('/fetch_affiliate_dashboard_report/:user_id', function($user_id) {
    $comm = ['amount' => '$100.00','date' => '04/21/2016-04/27/2016'];
    $commHistoryEntry1 = ['seller'=>'453 John Doe', 'sold_to'=>'9081 Debbie Johnson','product'=>'1001 Starter', 'date_purchase'=>'2016-02-26 12:23:22', 'sales_count'=>'10', 'amount'=>'100.00', 'commission_type'=>'Direct Commission'];
    $commHistoryEntry2 = ['seller'=>'453 John Doe', 'sold_to'=>'9025 Jane Roe','product'=>'1001 Starter', 'date_purchase'=>'2016-02-27 11:23:22', 'sales_count'=>'10', 'amount'=>'100.00', 'commission_type'=>'Direct Commission'];
    $direct1 = ['id'=>'10342', 'first_name'=>'John', 'last_name'=>'Roe', 'last_retail_sale'=>'04/15/2016'];
    $direct2 = ['id'=>'10344', 'first_name'=>'Jane', 'last_name'=>'Doe', 'last_retail_sale'=>'04/15/2016'];
    $sampleArray = [
        'current_rank' => 'International Director',
        'lifetime_earnings' => '$50000.00',
        'last_weekly_commission' => $comm,
        'commission_history' => [$commHistoryEntry1,$commHistoryEntry2],
        'total_pending_order' => '$1000.00',
        'qualified_directs' => [$direct1, $direct2]
    ];

    echo json_encode($sampleArray);

    /* TODO: Need to update this code
    $sql = "CALL sp_generateAffiliateDashboardReport(:user_id);";
    $db = Database::getInstance()->getDB();
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;

    echo $result[0]["v_json"];
    */
});

$app->post('/pay_commissions', function() {
    $edw = new EDW_Commission();
    $test = true;
    $kokard = new Propay($test);
    $db = Database::getInstance()->getDB();
    $paymentsLog = array();
    $testAccountNumber = '110324312106335764';

    // TODO:
    try {

        if (!(array_key_exists('data', $_POST))) {
            return 0;        
        } /* (array_key_exists('data', $_POST)) */
        
        $payoutIds = json_decode($_POST['data']);
        if (!($payoutIds)) {
            return 0;
        } /* (!($payoutIds)) */
        
        $i = 0;
        foreach ($payoutIds as $id) {
            $realamount = 0.00;
            $payoutDetails = $edw->getPayout($id);

            if (empty($payoutDetails)) {
                continue;
            } /* (empty($payoutDetails)) */
            
            if ($test) {
                $payoutDetails['account_number'] = $testAccountNumber;
            } /* ($test) */
            
            $realamount = floor($payoutDetails['value']);
            $paymentsLog[$i] =  array(
                                     'payout_id' => $payoutDetails['commission_payout_id']
                                    ,'name' => $payoutDetails['fname'] . ' ' . $payoutDetails['lname']
                                    ,'account_number' => $payoutDetails['account_number']
                                    ,'amount' => $realamount
                                );
            if (!empty($payoutDetails['account_number']) || ($realamount < 0)) {
                /* Pay commission using Ko-Kard. */
                $success = $kokard->payCommissions(
                                         $payoutDetails['account_number']
                                        ,$payoutDetails['value']
                                        ,$payoutDetails['commission_payout_id']
                                    );
                if ($success) {
                    $sql = "UPDATE cm_commission_payouts 
                            SET is_paid = 1 
                            WHERE (commission_payout_id = :payout_id)";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam('payout_id', $payoutDetails['commission_payout_id']);
                    $stmt->execute();
                    $paymentsLog[$i]['paid'] = 'YES';
                } else {
                    $paymentsLog[$i]['paid'] = 'NO';
                    die(var_dump($paymentsLog));
                } /* ($success) */
            } else {
                $paymentsLog[$i]['paid'] = 'NO, (No Account Number,or zero amount)';
            } /* (!empty($payoutDetails['account_number']) || $realamount < 0 ) */

            $i++;
        } /* foreach ($payoutIds as $id) */
        echo json_encode($paymentsLog);    
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}); /* pay_commissions */

$app->get('/markpaid/:periodid', function($period_id) {
    
    try {
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        $sql = "UPDATE cm_commission_payouts SET is_paid = 1 WHERE commission_period_id = :period_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("period_id", $period_id);
        $stmt->execute();

        $sql = "SELECT 
                      cpd.order_id
                    , cpd.user_id 
                FROM cm_commission_payout_details cpd
                INNER JOIN cm_commission_payouts cp
                WHERE cp.commission_period_id = :period_id AND cp.is_paid = 1 AND cpd.value < 0";

        $stmt = $db->prepare($sql);
        $stmt->bindParam("period_id", $period_id);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach($orders as $order){
            $sql2 = "UPDATE cm_commission_payout_refund 
                     SET is_paid = 1 
                     WHERE order_id = " . $order->order_id . " AND user_id = " . $order->user_id;
            $stmt = $db->prepare($sql2);
            $stmt->execute();
        }
        $db->commit();

        echo json_encode(array("period_id" => $period_id ));
    } catch(PDOException $e) {

        $db->rollBack();
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}); /* markpaid */

$app->get('/generatepayouts/:periodid', function($period_id) {

    try {
        $edw = new EDW_Commission(true);
        $result = $edw->generatePayouts($period_id);

        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}); /* generatepayouts */

$app->post('/computecommission', function() use ($app) {
    $periodType = $_POST['periodType'];
    $periodId = $_POST['periodId'];

    try {
        $edw = new EDW_Commission(true);
        $edw->setPeriodId($periodId);
        $edw->resetCommission($periodId);
        
        switch ($_POST['periodType']) {
            case '1': // Retail Commissions
            
                $edw->retailCommission();
                $edw->generateReport($periodType, $periodId);
                break;
            case '2': // Retail Pool Commissions

                $edw->retailPoolCommission();
                $edw->generateReport($periodType, $periodId);
                break;
            case '3': // Coded Commissions

                $edw->codedCommission();
                $edw->generateReport($periodType, $periodId);
                break;
            case '4': // Check Match Commissions

                $edw->checkMatchCommission();
                $edw->generateReport($periodType, $periodId);
                break;
            case '5': // Barry's Commissions

                $edw->barryCommission();
                $edw->generateReport($periodType, $_POST['periodId']);
                break;
        }
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
}); /* computecommission */

$app->post('/lockperiod', function() use ($app){

    try {
        $edw = new EDW_Commission(true);
        $edw->lockCommissionPeriod($_POST['periodId']);
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->get('/periodtypes/:active', function($active) {

    try {
        $sql = "SELECT * FROM cm_commission_period_types WHERE active = :active";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":active", $active);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/fetchhistoricalreport/:periodTypeId/:periodId/:dateFrom/:dateTo', 
        function($periodTypeId, $periodId, $dateFrom, $dateTo) {
            
    try {
        $sql = "CALL sp_generate_historical_comm_rpt(
                     :p_commission_period_type_id   -- IN p_commission_period_type_id INT(11)
                    ,:p_commission_period_id        -- IN p_commission_period_id INT(11)
                    ,:p_date_from                   -- IN p_date_from VARCHAR(30)
                    ,:p_date_to                     -- IN p_date_to VARCHAR(30)
                );";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":p_commission_period_type_id", $periodTypeId);
        $stmt->bindParam(":p_commission_period_id", $periodId);
        $stmt->bindParam(":p_date_from", $dateFrom);
        $stmt->bindParam(":p_date_to", $dateTo);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/getTotalCIPerAffiliate/:from/:to/:user_id', function($from, $to, $user_id) {
    
    try {
		$sql = "SELECT sp_getTotalCIPerAffiliate(:from, :to, :user_id) AS TotalCIPerAffiliate;";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":from", $from);
		$stmt->bindParam(":to", $to);
		$stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/get_retail_pool_comm/:from/:to/:user_id', function($from, $to, $user_id) {
    
    try {
		$sql = "
			SELECT 
				ROUND((
					sp_get_total_cv(:from, :to) / 2) / 
					sp_get_total_ci(:from, :to), 2) * 
					sp_get_total_ci_per_affiliate(:from, :to, :user_id) AS retail_pool;";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":from", $from);
		$stmt->bindParam(":to", $to);
		$stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/getTotalCI/:from/:to', function($from, $to) {
    
    try {
		$sql = "SELECT sp_getTotalCI(:from, :to) AS TotalCI;";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":from", $from);
		$stmt->bindParam(":to", $to);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/get_total_cv/:from/:to', function($from, $to) {
    
    try {
		$sql = "SELECT sp_get_total_cv(:from, :to) AS total_cv;";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":from", $from);
		$stmt->bindParam(":to", $to);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/periodspropay', function(){
    $sql = 'SElECT distinct(cpt.start_date), cpt.end_date from cm_commission_periods cpt WHERE locked = 1';

    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("type", $type);
        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($periods);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/fetchusersalescount/:userid', function($user_id) {

    try {
        $sql = "SELECT
                     `sp`.id AS `id`
                    ,`sp`.`name` AS `name`
                    ,sp_getSalesCount(:user_id,`sp`.id) AS `sales_count`
                    ,CONCAT(users.fname, ' ', users.lname) AS users_name
                    ,:user_id AS users_id
                FROM shoppingcart_products AS `sp`
                INNER JOIN users ON (users.id = :user_id)";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($result);
        $db = null;        
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}); /* generatepayouts */

$app->get('/saveusersalescount/:userid/:product_id/:sales_count', function($user_id, $product_sku, $sales_count) {

    try {
        $sql = "
            UPDATE cm_sales_count
            SET cm_sales_count.sales_count = :sales_count
            WHERE (cm_sales_count.user_id = :user_id) AND
                  (cm_sales_count.product_id = :product_id);
            ";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":product_id", $product_sku);
        $stmt->bindParam(":sales_count", $sales_count);
        $result = $stmt->execute();
        $db = null;

        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}); /* generatepayouts */

$app->get('/periodstest', function(){
    $sql = "SELECT cp.*,cpt.description FROM cm_commission_periods cp inner join cm_commission_period_types cpt using(commission_period_type_id) ";
    //$sql = 'SElECT distinct(cpt.start_date), cpt.end_date from cm_commission_periods cpt WHERE locked = 1';

    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("type", $type);
        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
       if(count($periods) > 0){
           echo 'ss';
           return;
       }

        echo 'jv pogi';
        //echo json_encode($periods);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/periods', function($type) {

    $sql = "CALL sp_fetch_commission_period('UNLOCKED-ONLY', :type)";
    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":type", $type);
        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($periods);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/lockedperiods/:type', function($type) {

    try {
        $sql = "CALL sp_fetch_commission_period('LOCKED-ONLY', :type)";
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":type", $type);
        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($periods);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/periodsall/:type', function($type){
    $sql = "SELECT * FROM cm_commission_periods WHERE commission_period_type_id = :type";

    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("type", $type);
        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($periods);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/periodslocked/:type', function($type){
    $sql = "SELECT * FROM cm_commission_periods WHERE commission_period_type_id =:type AND locked=1";

    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("type", $type);
        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($periods);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/periods/:type', function($type){
    $sql = "SELECT * FROM cm_commission_periods WHERE commission_period_type_id =:type AND locked <>1";

    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("type", $type);
        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($periods);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/updateperiods', function(){

    try {

        $db = Database::getInstance()->getDB();

        $sql = "select max(end_date) as end_date from cm_commission_periods WHERE commission_period_type_id=2 ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $period = $stmt->fetch(PDO::FETCH_ASSOC);
        $start_date = strtotime("+1 days", strtotime($period['end_date']));
        $end_date = strtotime("+6 days",$start_date);
        //echo  date("Y-m-d", $start_date) . ' to ' . date("Y-m-d", $end_date);

        $sql = "INSERT INTO cm_commission_periods (commission_period_type_id,start_date,end_date)
                    VALUES ('2',:start_date,:end_date)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':start_date', date("Y-m-d", $start_date));
        $stmt->bindParam(':end_date', date("Y-m-d", $end_date));
        $stmt->execute();

        $sql = "INSERT INTO cm_commission_periods (commission_period_type_id,start_date,end_date)
                    VALUES ('3',:start_date,:end_date)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':start_date', date("Y-m-d", $start_date));
        $stmt->bindParam(':end_date', date("Y-m-d", $end_date));
        $stmt->execute();

        echo  date("Y-m-d", $start_date) . ' to ' . date("Y-m-d", $end_date);

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/groups', function(){
    $sql = "select * FROM cm_groups";
    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->query($sql);
        $groups = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '{"groups": ' . json_encode($groups) . '}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/group/:groupid', function ($groupid) {
    //$sql = "select * FROM cm_groups";
	$sql = "SELECT * FROM cm_groups WHERE group_id=:groupid";
	try {
		$db = Database::getInstance()->getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("groupid", $groupid);
		$stmt->execute();
		$wine = $stmt->fetchObject();  
		$db = null;
		echo json_encode($wine); 
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
});

$app->get('/membernotifications/:userid', function($userid){

    $sql = 'Select m.fname,m.lname,date(os.timestamp) as date_attended,p.name as ticket,p.sku,os.order_status_type_id as status_id,ost.name as status ';
    $sql .= 'from cm_orders o join users m on o.user_id=m.id ';
    $sql .= 'join users m2 on m.sponsorid=m2.id  ';
    $sql .= 'join cm_products p on p.sku=o.product_sku  ';
    $sql .= 'join cm_order_statuses os using(order_id)  ';
    $sql .= 'join cm_order_status_types ost using(order_status_type_id)  ';
    $sql .= 'where m2.id=:userid and order_id not in(Select order_id from cm_order_statuses where order_status_type_id=4) ';
    $sql .= 'order by o.timestamp desc ';


    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("userid", $userid);
        $stmt->execute();
        $member_notify = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($member_notify);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/pendingreferrals/:userid', function($user_id){
    try {
        $edw = new EDW_Commission(true);
        $result = $edw->getPendingReferrals($user_id);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/getsalescount/:userid', function($user_id) {

    try {
        $edw = new EDW_Commission(true);
        $result = $edw->getProductsSalesCountReport($user_id);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/affiliatereport/:userid', function($user_id){
    try {
        $edw = new EDW_Commission(true);
        $result = $edw->getAffilliateEarnings($user_id);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/affiliatepassupreport/:userid', function($user_id) {

    try {

        $edw = new EDW_Commission(true);
        $result = $edw->getAffiliatePassUp($user_id);
        
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/affiliatepropay/:userid', function($user_id) {
    $sql = "SELECT * FROM propay_info WHERE user_id=:user_id";
    
    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", $user_id);
        $stmt->execute();
        $users = $stmt->fetchObject();
        $db = null;
        
        echo json_encode($users);
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
});

$app->post('/updatepropayinfo/', function() use ($app){

    $sql = '';
    $db = Database::getInstance()->getDB();

    if($_POST['is_new'] == '1'){
        $sql = 'INSERT INTO propay_info (user_id,first_name,last_name,account_number)
                VALUES (:user_id,:first_name,:last_name,:account_number)';
    }else{
        $sql = 'UPDATE propay_info SET first_name=:first_name,last_name=:last_name,account_number=:account_number WHERE user_id=:user_id';
    }
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $_POST['user_id']);
    $stmt->bindParam(':first_name',$_POST['first_name']);
    $stmt->bindParam(':last_name', $_POST['last_name']);
    $stmt->bindParam(':account_number',$_POST['account_number']);
    $stmt->execute();

    echo '{"id":"1"}';

});

$app->get('/getoverridecommissions/', function(){
    $sql = "SELECT o.override_id,CONCAT(u.lname,', ',u.fname) as full_name,o.percentage,DATE(o.timestamp) as timestamp
        FROM cm_manual_commission_override o inner join users u on (u.id = o.user_id) ";
    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $override_commissions = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($override_commissions);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/getwiretransfers/', function(){

    $sql = "SELECT sp.id as order_id,CONCAT(p.sku,' ', p.name) as product_name,p.id as product_id,DATE(sp.purchasedate) as purchasedate,u.sponsorid,u.id as user_id,
            CONCAT(u.id,' ', u.fname,' ',u.lname) as user_name,p.price,sp.is_wire_transfered,DATE(sp.wire_date_paid) as wire_date_paid,sp.wire_reference_number
            FROM shoppingcart_purchases sp
            INNER JOIN shoppingcart_products p ON (sp.productid = p.id)
            INNER JOIN users u ON (sp.userid = u.id)
            WHERE p.payment_type = 1 ";
    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $wire_transfers = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($wire_transfers);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->post('/updatewiretransfer/', function() use ($app){

    try {
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();

        $sql = "UPDATE shoppingcart_purchases 
				SET  is_wire_transfered = 1
					,wire_reference_number = :reference_number
					,wire_date_paid = :date_paid
					,purchasedate = :date_paid
					,billdate = :date_paid
					,active = 1
				WHERE (id = :order_id)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':order_id', $_POST['order_id'], PDO::PARAM_INT);
        $stmt->bindParam(':reference_number', $_POST['reference_number']);
        $stmt->bindParam(':date_paid', $_POST['date_paid']);
        $stmt->execute();

        $sql = "
			INSERT INTO transactions(
				 userid
				,sponsorid
				,itemid
				,transactiondate
				,billmethod
				,is_wire_transfered
				,wire_reference_number
				,wire_date_paid
				,`status`
				,type)
			VALUES(
				 :user_id
				,:sponsor_id
				,:product_id
				,:date_paid
				,'WIRE'
				,1
				,:reference_number
				,:date_paid
				,'Approved'
				,'product');";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $_POST['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':sponsor_id', $_POST['sponsor_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $_POST['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':reference_number', $_POST['reference_number']);
        $stmt->bindParam(':date_paid', $_POST['date_paid']);
        $stmt->execute();

        $db->commit();
        echo '{"status":"success", "details": {"orderStatusId" : '.$_POST['order_id'].', "orderStatus" : "pending"}}';
    }catch(PDOException $e) {
        $db->rollBack();
        echo '{"status":"error", "details":'.$e->getMessage().'}';
    }

});

$app->get('/getuser/:userid',function($user_id){

    $sql = "SELECT 
                DISTINCT
                u.id as id,
                u.fname as fname,
                u.lname as lname,
                u.country as country,
                CONCAT(s.fname,' ',s.lname) as sponsor_name,
                IFNULL(r.name, '') as rank,
                (
                SELECT IFNULL(DATE_FORMAT(max(t.transactiondate), '%M %d, %Y'),'') FROM transactions t 
                INNER JOIN categorymap cm2 ON cm2.userid = t.userid
                WHERE t.sponsorid = u.id AND cm2.catid = ".CUSTOMER."
                ) as last_retail_sale
                
                FROM cm_nodes cmn
                
                INNER JOIN users u ON cmn.member_id = u.id
                INNER JOIN users s ON cmn.parent_id = s.id
                LEFT JOIN ranks r ON r.id = u.rank_id
                
                WHERE cmn.member_id = :user_id and u.levelid=3";
    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", $user_id);
        $stmt->execute();
        $users = $stmt->fetchObject();
        $db = null;
        echo json_encode($users);

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

});

$app->get('/getChildren/:userid', function($user_id){
    try {
        $edw = new ELM_Commission(true);
        $result = $edw->getChildren($user_id);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/getusers2', function(){
    $wild_card=$_GET['term'];
    $sql = "SELECT CONCAT(id,',',lname,' ',fname) as fullname
            FROM users
            WHERE id like '%".$wild_card."%' OR lname like '%".$wild_card."%' OR fname like '%".$wild_card."%'";
    try {
        $user_arr2 = array();
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        while ($period = $stmt->fetchObject()){
            $user_arr2[] = $period->fullname;
        }
        $db = null;
        echo json_encode($user_arr2);

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/getusers', function(){
    $wildcard=$_GET['term'];
	$field=$_GET['field'];
    $sql = "SELECT id,CONCAT(lname,' ',fname) as fullname FROM users
					WHERE ".$field." like  '%".$wildcard."%' ";	
				
    try {       
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
        $json_arr = array();
		foreach($result as $row){
			$json_row['id'] = $row['id'];
			$json_row['label'] = $row['fullname'];
			$json_row['value'] = $row['fullname'];
			array_push($json_arr,$json_row);
		}
        echo json_encode($json_arr);

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});


$app->get('/getusersoverride', function(){
    $wild_card=$_GET['term'];
    $sql = "SELECT CONCAT(id,',',lname,' ',fname) as fullname
            FROM users
            WHERE id like '%".$wild_card."%' OR lname like '%".$wild_card."%' OR fname like '%".$wild_card."%'
            AND id NOT IN (SELECT user_id from cm_manual_commission_override) ";
    try {
        $user_arr2 = array();
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        while ($period = $stmt->fetchObject()){
            $user_arr2[] = $period->fullname;
        }
        $db = null;
        echo json_encode($user_arr2);

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->post('/addcommissionoverride', function() use ($app){

    try {
        $db = Database::getInstance()->getDB();

        $sql =  "INSERT INTO cm_manual_commission_override(user_id,percentage) VALUES (:user_id,:percentage)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $_POST['hd_user']);
        $stmt->bindParam(':percentage', $_POST['txt_amount']);
        $stmt->execute();

        $user_id = $db->lastInsertId();
        echo json_encode(array('user_id'=>$user_id));

    }catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }

});


$app->get('/deletecommissionoverride/:override_id', function($override_id) use ($app){

    try {
        $db = Database::getInstance()->getDB();

        $sql =  "DELETE FROM cm_manual_commission_override WHERE override_id = :override_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':override_id',$override_id);
        $stmt->execute();

        echo json_encode(array('user_id'=>$override_id));

    }catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }

});

$app->get('/historicalreferrals/:userid/:periodid', function($user_id,$period_id){
    try {
        $edw = new EDW_Commission(true);
        $result = $edw->getHistoricalReferrals($user_id,$period_id);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->get('/generatepayouts_date_range/:commission_types/:start_date/:end_date', function($commission_types, $start_date, $end_date){
    try {
        $selected_period_types = explode('&', $commission_types);
        $commission_payouts = array();
        $sql = "select payout.commission_payout_id as payout_id, payout_detail.order_id as order_id ,payout.value, u.id, u.fname, u.lname, u.active as status, pi.account_number as account_number, payout_type.name as payout_type 
                from cm_commission_payouts as payout
                inner join cm_commission_payout_details as payout_detail on payout_detail.commission_payout_detail_id = payout.commission_payout_id
                inner join cm_commission_payout_types as payout_type on payout_type.commission_payout_type_id = payout.commission_payout_type_id
                inner join cm_commission_periods as period on period.commission_period_id = payout.commission_period_id
                inner join users as u on u.id = payout.user_id
                left join propay_info as pi on pi.user_id = u.id
                where payout.commission_payout_type_id = :payout_type_id and period.start_date >= :start_date and period.end_date <= :end_date and payout.is_paid is null and period.locked = 1 and payout.commission_payout_type_id = :payout_type_id and payout.is_paid is NULL";

        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        $stmt = $db->prepare($sql);
        $stmt->bindParam('start_date', $start_date);
        $stmt->bindParam('end_date', $end_date);
        
        if (in_array(DIRECT_COMMISSION_PAYOUT_ID, $selected_period_types)) {
            $payout_type_id = DIRECT_COMMISSION_PAYOUT_ID;
            $stmt->bindParam("payout_type_id", $payout_type_id);
            $stmt->execute();
            $commission_payouts['direct_commissions'] = $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        else {
            $commission_payouts['direct_commissions'] = NULL;
        }
        
        if (in_array(OVERRIDE_COMMISSION_PAYOUT_ID, $selected_period_types)) {
            $payout_type_id = OVERRIDE_COMMISSION_PAYOUT_ID;
            $stmt->bindParam("payout_type_id", $payout_type_id);
            $stmt->execute();
            $commission_payouts['override_commissions'] = $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        else {
            $commission_payouts['override_commissions'] = NULL;
        }

        // print_r($commission_payouts); exit();

        echo json_encode($commission_payouts);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->post('/orderreports', function(){
    try {
        $date_from = $_POST['datefrom'];
        $date_to = $_POST['dateto'];

        $edw = new EDW_Commission(true);
        $result = $edw->getOrderReports($date_from,$date_to);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});


$app->get('/refundablepayouts/:userid', function($user_id){
    $sql = "SELECT p.sku, t.id AS order_id, t.amount, date(t.transactiondate) AS order_date, t.new AS subscription, 
						p.name AS product, cpr.is_paid,cpr.in_payout,cpr.commission_payout_refund_id, 
						t.userid as user_id
						FROM transactions t
						JOIN shoppingcart_products p ON p.id = t.itemid						
						LEFT JOIN cm_commission_payout_refund cpr ON cpr.order_id = t.id
						WHERE t.userid =:user_id group by t.id";
    try {
        $db = Database::getInstance()->getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", $user_id);
        $stmt->execute();
        $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        echo json_encode($payouts);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

$app->post('/setrefund', function() use ($app){

    try {
        
        $order_id = $_POST['order_id'];
        $user_id = $_POST['user_id'];
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        
        $sql1 = "Select cp.user_id,cp.value,p.locked,cp.commission_payout_id 
					from cm_commission_payouts cp join cm_commission_payout_details cpd using(commission_payout_id) 
					join cm_commission_periods p using(commission_period_id)
					where cpd.order_id=:order_id";
        
        $stmt = $db->prepare($sql1);
        $stmt->bindParam (':order_id', $order_id);              
        $stmt->execute();        
        $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if($stmt->rowCount() > 0){
			$sql = "insert into cm_commission_payout_refund(user_id,commission_payout_id,order_id,amount,is_paid) 
                        values(:user_id,:commission_payout_id,:order_id,:amount,:is_paid)";
			foreach($payouts as $p){
				if($p['locked'] == 0){
					$sql2 = "Delete from cm_commission_payouts where commission_payout_id=:payout_id";
					$stmt = $db->prepare($sql2);					
					$stmt->bindParam (':payout_id', $p['commission_payout_id']);
					$stmt->execute();
						
					$sql3 = "Delete from cm_commission_payout_details where commission_payout_id=:payout_id";
					$stmt = $db->prepare($sql3);					
					$stmt->bindParam (':payout_id', $p['commission_payout_id']);
					$stmt->execute();
				}else{
					$stmt = $db->prepare($sql);
					$stmt->bindParam (':user_id', $p['user_id']);
					$stmt->bindParam (':commission_payout_id', $p['commission_payout_id']);
					$stmt->bindParam (':order_id', $order_id);              
					$stmt->bindParam (':amount', $p['value']);              
					$stmt->bindParam (':is_paid', $p['locked']);  
					$stmt->execute();
				}
			}
		}else{
			$sql = "insert into cm_commission_payout_refund(user_id,commission_payout_id,order_id,amount,is_paid) 
                        values(:user_id,0,:order_id,0,0)";
			$stmt = $db->prepare($sql);
			$stmt->bindParam (':user_id', $user_id);
			$stmt->bindParam (':order_id', $order_id);
			$stmt->execute();
		}
        $db->commit();
        
        echo json_encode(array("order_id" => $_POST['order_id'] ));

    }catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }

});

$app->post('/commissionreport', function() use ($app){

    try {

        $evt = new EVT_Commission(true);
        $evt->setPeriodId($_POST['periodId']);

        switch($_POST['periodType']){
            case '2':
                $evt->generateReportAlt(2);
                break;
            case '3':
                $evt->generateReportAlt(3);
                break;
            case '4':
                $evt->generateReportAlt(4);
                break;
        }

    }catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }

});


/* ----------- This section is for the clients API endpoint ------ */

$app->post('/addshoppingcartorder/', function() use ($app){
    try{
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        $edw = new EDW_Commission(true);

        $user_id = $_POST['userId'];
        if(!$edw->checkUserExist($user_id)){
            $query = "INSERT INTO users (sponsorid,site,active,fname,lname,group_id,email,password,dayphone)
                VALUES (:sponsorid,:site_name,'Yes',:fname,:lname,'1',:email,:password,:day_phone)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':sponsorid', $_POST['sponsorId'], PDO::PARAM_INT);
            $stmt->bindParam(':fname', $_POST['customerFirstName'], PDO::PARAM_STR);
            $stmt->bindParam(':lname', $_POST['customerLastName'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $_POST['customerEmail'], PDO::PARAM_STR);
            $stmt->bindParam(':site_name', $_POST['customerSite']);
            $stmt->bindParam(':password', $_POST['customerPassword']);
            $stmt->bindParam(':day_phone', $_POST['customerPhone']);
            $stmt->execute();
            $user_id = $db->lastInsertId();
        }

        $sql = "INSERT INTO transactions (userid,sponsorid,itemid,transactiondate,billmethod)
                    VALUES (:user_id,:sponsor_id,:product_id,:date_paid,'WIRE')";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sponsor_id', $_POST['sponsorId']);
        $stmt->bindParam(':product_id', $_POST['productId']);
        $stmt->bindParam(':date_paid', date('Y/m/d'));
        $stmt->execute();
        $order_id=$db->lastInsertId();

        /*
        $query = "INSERT INTO cm_user_product_eligibility (user_id,product_id) VALUES (:user_id,:product_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $_POST['productId']);
        $stmt->execute();


        $edw->processCommission($user_id,$order_id,$_POST['productId']);
        */
        $db->commit();
        echo '{"status":"success", "details": {"orderId" : '.$order_id.', "orderStatus" : "pending"}}';

    }catch (PDOException $ex){
        $db->rollBack();
        echo '{"status":"error", "details":'.$ex->getMessage().'}';
    }
});

$app->post('/addregistrationorder/:token', function($token) use ($app){

    try {
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        //$evt = new EVT_Commission();
        $level_id = 4;
        $order_id = '';
        $user_id = '';

        if($token != ACCESS_TOKEN){
            echo '{"status":"error", "details":"Invalid Access Token."}';
            die();
        }

        if(!$evt->productExists($_POST['productSKU'])){
            echo '{"status":"error", "details":"Product does not exist."}';
            die();
        }

        $sql =  "INSERT INTO users(sponsorid,site, fname,lname,email,active) VALUES (:sponsor_id,:site,:fname,:lname,:email,'YES')";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':sponsor_id', $_POST['sponsorId'], PDO::PARAM_INT);
        $stmt->bindParam(':fname', $_POST['customerFirstName'], PDO::PARAM_STR);
        $stmt->bindParam(':site', strtolower($_POST['customerFirstName']), PDO::PARAM_STR);
        $stmt->bindParam(':lname', $_POST['customerLastName'], PDO::PARAM_STR);
        $stmt->bindParam(':email', $_POST['customerEmail'], PDO::PARAM_STR);
        $stmt->execute();
        $user_id = $db->lastInsertId();

        $sql = "INSERT INTO cm_orders (user_id,total_amount,tax_amount,product_sku,quantity,external_transaction_id,order_type_id,volume)
                VALUES (:user_id,:total_amount,:tax_amount,:product_sku,:quantity,:external_id,'1',:volume)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':total_amount', $_POST['totalAmount']);
        $stmt->bindParam(':tax_amount', $_POST['taxAmount']);
        $stmt->bindParam(':product_sku', $_POST['productSKU']);
        $stmt->bindParam(':quantity',$_POST['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':external_id',$_POST['transactionId'], PDO::PARAM_INT);
        $stmt->bindParam(':volume', $_POST['volume']);
        $stmt->execute();
        $order_id = $db->lastInsertId();

        $sql = "INSERT INTO cm_order_statuses (order_id,order_status_type_id) VALUES (:order_id,'5')";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();

        $db->commit();
        echo '{"status":"success", "details": {"orderId" : '.$order_id.', "orderStatus" : "pending"}}';


    }catch(PDOException $e) {
        $db->rollBack();
        echo '{"status":"error", "details":'.$e->getMessage().'}';
    }

});

$app->post('/updateorderstatus/:token', function($token) use ($app){

    try {
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        $evt = new EVT_Commission();


        if($token != ACCESS_TOKEN){
            echo '{"status":"error", "details":"Invalid Access Token."}';
            die();
        }

        if(!$evt->orderExists($_POST['orderId'])){
            echo '{"status":"error", "details":"Order does not exist."}';
            die();
        }

        if($_POST['orderStatusTypeId'] == 0 || $_POST['orderStatusTypeId'] > 5){
           echo 'status":"error", "details":"Invalid Order Status Type ID. Please check API document or contact customer support."}';
           die();
        }

        $sql = "UPDATE cm_order_statuses SET order_status_type_id=:order_status_id,timestamp=now()  WHERE order_id=:order_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':order_id', $_POST['orderId'], PDO::PARAM_INT);
        $stmt->bindParam(':order_status_id',$_POST['orderStatusTypeId'], PDO::PARAM_INT);
        $stmt->execute();

        $db->commit();
        echo '{"status":"success", "details": {"orderStatusId" : '.$_POST['orderId'].', "orderStatus" : "pending"}}';


    }catch(PDOException $e) {
        $db->rollBack();
        echo '{"status":"error", "details":'.$e->getMessage().'}';
    }

});

$app->post('/addmemberpoints/:token', function($token) use ($app){

    try {
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        $evt = new EVT_Commission();

        if($token != ACCESS_TOKEN){
            echo '{"status":"error", "details":"Invalid Access Token."}';
            die();
        }

        if(!$evt->userExists($_POST['memberId'])){
            echo '{"status":"error", "details":"Member does not exist in the system."}';
            die();
        }

        $sql = "INSERT INTO cm_member_points (user_id,points,transaction_type,note) VALUES (:user_id,:points,'Added',:note)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $_POST['memberId'], PDO::PARAM_INT);
        $stmt->bindParam(':points', $_POST['points']);
        $stmt->bindParam(':note', $_POST['note'], PDO::PARAM_STR);
        $stmt->execute();
        $points_id = $db->lastInsertId();
        $db->commit();
        echo '{"status":"success", "details": {"transactionId" : '.$points_id.'}}';


    }catch(PDOException $e) {
        $db->rollBack();
        echo '{"status":"error", "details":'.$e->getMessage().'}';
    }

});

$app->post('/redeemmemberpoints/:token', function($token) use ($app){

    try {
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        $evt = new EVT_Commission();

        if($token != ACCESS_TOKEN){
            echo '{"status":"error", "details":"Invalid Access Token."}';
            die();
        }

        if(!$evt->userExists($_POST['memberId'])){
            echo '{"status":"error", "details":"Member does not exist in the system."}';
            die();
        }

        $available_points = $evt->getAvailablePoints($_POST['memberId']);
        if($available_points < 0 ){
            echo '{"status":"error", "details":"You do not have enough points to redeem."}';
            die();
        }

        $sql = "INSERT INTO cm_member_points (user_id,points,transaction_type,note) VALUES (:user_id,:points,'Redeemed',:note)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $_POST['memberId'], PDO::PARAM_INT);
        $stmt->bindParam(':points', $_POST['points']);
        $stmt->bindParam(':note', $_POST['note'], PDO::PARAM_STR);
        $stmt->execute();
        $points_id = $db->lastInsertId();
        $db->commit();
        echo '{"status":"success", "details": {"transactionId" : '.$points_id.'}}';


    }catch(PDOException $e) {
        $db->rollBack();
        echo '{"status":"error", "details":'.$e->getMessage().'}';
    }

});
/* --------------------- END HERE -------------------------------- */

/* --------------------- CRON FUNCTIONS -------------------------------- */

$app->get('/processhoppingcartorders', function(){
    try{
        $db = Database::getInstance()->getDB();
        $db->beginTransaction();
        $edw = new EDW_Commission(true);

        $sql= "SELECT * FROM transactions WHERE is_processed IS NULL AND status='Approved' AND itemid IN (29,30,31,32,33,34,35,36,37,40,41,42,43,44,45,46,47,48,49) LIMIT 1";
        $stmt =$db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // if not empty result
        if (is_array($result))  {

            $query = "UPDATE transactions SET is_processed = 1 WHERE id=:order_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $result['id']);
            $stmt->execute();

            $query = "INSERT INTO cm_user_product_eligibility (user_id,product_id) VALUES (:user_id,:product_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $result['userid']);
            $stmt->bindParam(':product_id', $result['itemid']);
            $stmt->execute();

            $edw->processCommission($result['userid'],$result['id'],$result['itemid']);

            $db->commit();
            echo '{"status":"success", "details": {"orderId" : '.$result['id'].', "orderStatus" : "pending"}}';
        }else {
            echo '{"status":"error", "details": {"orderId" : "1", "orderStatus" : "pending"}}';
        }
    }catch (PDOException $ex){
        $db->rollBack();
        echo '{"status":"error", "details":'.$ex->getMessage().'}';
    }
});

$app->post('/ewallet_members_save', function() {
    
    try {

        if (!(array_key_exists('data', $_POST))) {
            return 0;        
        } /* (array_key_exists('data', $_POST)) */
        
        $db = Database::getInstance()->getDB();
        $query = "INSERT INTO cm_e (user_id,product_id) VALUES (:user_id,:product_id)";
        $stmt = $db->prepare($query);
    } catch(PDOException $e) {
        echo '{"error" : {"text":'. $e->getMessage() .'}}';
    }
}); /* pay_commissions */

/* --------------------- END HERE -------------------------------- */

$app->run();

 function leading_zeros($value, $places){
    // Function written by Marcus L. Griswold (vujsa)
    // Can be found at http://www.handyphp.com
    // Do not remove this header!
	$leading= "";
	if(is_numeric($value)){
		for($x = 1; $x <= $places; $x++){
			$ceiling = pow(10, $x);
			if($value < $ceiling){
				$zeros = $places - $x;
				for($y = 1; $y <= $zeros; $y++){
					$leading .= "0";
				}
				$x = $places + 1;
			}
		}
		$output = $leading . $value;
	}
	else{
		$output = $value;
	}
	return $output;
}

?>
