<?php

require_once('commissions/edw.commission.php');
require_once('includes/db.config.php');
require_once('includes/DB.class.new.php');

try {
	$db = Database::getInstance()->getDB();
    $stmt = $db->prepare("CALL sp_fetch_transactions('NOT-PROCESSED');");
	$stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$stmt->closeCursor();
    if (is_array($result)) {
		$edw = new EDW_Commission(true);
		$edw->processCommission($result[0]['id']);
        echo '{"status":"success", "details": {"orderId" : '.$result[0]['id'].', "orderStatus" : "pending"}}';
    } else {
        echo '{"status":"success", "details": {"orderId" : "-1", "orderStatus" : "none"}}';
    }
}
catch (PDOException $ex) {	
    echo '{"status":"error", "details":'.$ex->getMessage().'}';
}

?>