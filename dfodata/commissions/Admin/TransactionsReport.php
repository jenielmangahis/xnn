<?php

namespace Commissions\Admin;
use Carbon\Carbon;
use Commissions\QueryHelper;
use Illuminate\Support\Facades\DB;
use PDO;
use Commissions\CsvReport;

class TransactionsReport
{
     const REPORT_PATH = "csv/admin/transaction_report";

     public function getTransactionsDateRange($start_date, $end_date, $status = "All")
     {
          $db = DB::connection()->getPdo();

          $sql = $this->getQuery($start_date, $end_date, $status);

          $stmt = $db->prepare($sql);
          $stmt->bindParam(':start_date', $start_date);
          $stmt->bindParam(':end_date', $end_date);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          return $result;
     }

     private function NotExistsUnderBen($column)
     {
          // return " 1 = 1 ";
          return "
               NOT EXISTS (
                    WITH RECURSIVE downline (user_id, parent_id, `level`) AS (
                         SELECT 
                         id AS user_id,
                         sponsorid AS parent_id,
                         1 AS `level`
                         FROM users
                         WHERE id = 20
                         
                         UNION ALL
                         
                         SELECT
                         p.id AS user_id,
                         p.sponsorid AS parent_id,
                         downline.`level` + 1 `level`
                         FROM users p
                         INNER JOIN downline ON p.sponsorid = downline.user_id AND p.levelid = 3
                    )
                    SELECT 1 FROM downline d WHERE d.user_id = $column
               )
          ";
     }

     public function getTotalAmountsPerTransactionType($start_date, $end_date, $status = "All")
     {

          $db = DB::connection()->getPdo();

          $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('Y-m-d');
          $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('Y-m-d');

          $and_where = "";

          if(in_array($status, ['Approved', 'Declined', 'Error', 'Failed'])) {
               $and_where .= " AND t.status = '$status' ";

               if($status == 'Approved') {
                    $and_where .= " AND EXISTS(SELECT 1 FROM v_cm_transactions tt WHERE tt.transaction_id = t.id AND DATE(tt.transactiondate) BETWEEN '$start_date' AND '$end_date')";
               }
          }

          $query = "
               SELECT
                    'Credit Card' AS tags,
                    CONCAT('$', FORMAT(IFNULL(SUM(t.amount), 0), 2)) AS over_all
               FROM transactions t
               WHERE 
                    t.type = 'product'
                    AND DATE(t.transactiondate) BETWEEN '$start_date' AND '$end_date'
                    AND " . $this->NotExistsUnderBen('t.userid') . "
                    $and_where
                    
               UNION ALL
               
               SELECT
                    'Ledger' AS tags,
                    '$0.00' AS over_all -- wala pa man ni na implement
                    
               UNION ALL
               
               SELECT
                    'Gift Cards' AS tags,
                    CONCAT('$',FORMAT(IFNULL(SUM(
                    (
                         SELECT IFNULL(SUM(och.amount), 0) * -1
                         FROM oc_coupon_history och
                         WHERE och.order_id = t.id
                    ) + (
                         SELECT IFNULL(SUM(gch.amount), 0) * -1
                         FROM gift_cards_history gch
                         WHERE gch.transaction_id = t.id
                    )
                    ), 0), 2)) AS over_all
               FROM transactions t
               WHERE 
                    t.type = 'product'
                    AND DATE(t.transactiondate) BETWEEN '$start_date' AND '$end_date'
                    AND " . $this->NotExistsUnderBen('t.userid') . "
                    $and_where
          
          ";

          $stmt = $db->prepare($query);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          return $result;
     }

     public function getTotalAmountsPerTransactionTypeV2($start_date, $end_date)
     {

          $db = DB::connection()->getPdo();

          $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('Y-m-d');
          $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('Y-m-d');

     //        $and_where = "";
     //
     //        if(in_array($status, ['Approved', 'Declined', 'Error', 'Failed'])) {
     //            $and_where .= " AND t.status = '$status' ";
     //
     //            if($status == 'Approved') {
     //                $and_where .= " AND EXISTS(SELECT 1 FROM v_cm_transactions tt WHERE tt.transaction_id = t.id AND DATE(tt.transactiondate) BETWEEN '$start_date' AND '$end_date')";
     //            }
     //        }

          $query = "
               SELECT
                    'Credit Card' AS tags,
                    CONCAT('$', FORMAT(IFNULL(SUM(t.amount), 0), 2)) AS over_all,
                    CONCAT('$', FORMAT(IFNULL((SELECT SUM(amount) FROM transactions WHERE type='product' AND status='Approved' AND DATE(transactiondate) BETWEEN '$start_date' AND '$end_date' AND " . $this->NotExistsUnderBen('userid') . "), 0), 2)) AS approved,
                    CONCAT('$', FORMAT(IFNULL((SELECT SUM(amount) FROM transactions WHERE type='product' AND status='Declined' AND DATE(transactiondate) BETWEEN '$start_date' AND '$end_date'), 0), 2)) AS declined,
                    CONCAT('$', FORMAT(IFNULL((SELECT SUM(amount) FROM transactions WHERE type='product' AND status='Error' AND DATE(transactiondate) BETWEEN '$start_date' AND '$end_date'), 0), 2)) AS error,
                    CONCAT('$', FORMAT(IFNULL((SELECT SUM(amount) FROM transactions WHERE type='product' AND status='Failed' AND DATE(transactiondate) BETWEEN '$start_date' AND '$end_date'), 0), 2)) AS failed
               FROM transactions t
               WHERE 
                    t.type = 'product'
                    AND DATE(t.transactiondate) BETWEEN '$start_date' AND '$end_date'
                    AND " . $this->NotExistsUnderBen('t.userid') . "
                    
                    
               UNION ALL
               
               SELECT
                    'Ledger' AS tags,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(cl.amount), 0) * - 1 FROM cm_ledger cl WHERE cl.reference_number = t.id AND cl.type = 'sale')), 0),2)) AS over_all,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(cl.amount), 0) * - 1 FROM cm_ledger cl WHERE cl.reference_number = t.id AND t.status ='Approved' AND cl.type = 'sale')), 0),2)) AS approved,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(cl.amount), 0) * - 1 FROM cm_ledger cl WHERE cl.reference_number = t.id AND t.status ='Declined' AND cl.type = 'sale')), 0),2)) AS declined,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(cl.amount), 0) * - 1 FROM cm_ledger cl WHERE cl.reference_number = t.id AND t.status ='Error' AND cl.type = 'sale')), 0),2)) AS error,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(cl.amount), 0) * - 1 FROM cm_ledger cl WHERE cl.reference_number = t.id AND t.status ='Failed' AND cl.type = 'sale')), 0),2)) AS failed
                    FROM transactions t
               WHERE 
                    t.type = 'product'
                    AND DATE(t.transactiondate) BETWEEN '$start_date' AND '$end_date'
                    AND " . $this->NotExistsUnderBen('t.userid') . "
               UNION ALL
               
               SELECT
                    'Gift Cards' AS tags,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(gch.amount), 0) * - 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id)), 0),2)) AS over_all,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(gch.amount), 0) * - 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id AND t.status ='Approved')), 0),2)) AS approved,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(gch.amount), 0) * - 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id AND t.status ='Declined')), 0),2)) AS declined,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(gch.amount), 0) * - 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id AND t.status ='Error')), 0),2)) AS error,
                    CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(gch.amount), 0) * - 1 FROM gift_cards_history gch WHERE gch.transaction_id = t.id AND t.status ='Failed')), 0),2)) AS failed
               FROM transactions t
               WHERE 
                    t.type = 'product'
                    AND DATE(t.transactiondate) BETWEEN '$start_date' AND '$end_date'
                    AND " . $this->NotExistsUnderBen('t.userid') . "
          
               UNION ALL
               
               SELECT
               'Coupons' AS tags,
               CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(och.amount), 0) * -1 FROM oc_coupon_history och WHERE och.order_id = t.id)), 0), 2)) AS over_all,
               CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(och.amount), 0) * -1 FROM oc_coupon_history och WHERE och.order_id = t.id AND t.status ='Approved')), 0), 2)) AS approved,
               CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(och.amount), 0) * -1 FROM oc_coupon_history och WHERE och.order_id = t.id AND t.status ='Declined')), 0), 2)) AS declined,
               CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(och.amount), 0) * -1 FROM oc_coupon_history och WHERE och.order_id = t.id AND t.status ='Error')), 0), 2)) AS error,
               CONCAT('$',FORMAT(IFNULL(SUM((SELECT IFNULL(SUM(och.amount), 0) * -1 FROM oc_coupon_history och WHERE och.order_id = t.id AND t.status ='Failed')), 0), 2)) AS failed
               FROM transactions t
               WHERE 
                    t.type = 'product'
                    AND DATE(t.transactiondate) BETWEEN '$start_date' AND '$end_date'
                    AND " . $this->NotExistsUnderBen('t.userid') . "
          ";

          $stmt = $db->prepare($query);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          return $result;
     }

     public function getReportCSV($start_date, $end_date, $status = "All")
     {
          $db = DB::connection()->getPdo();

          $sql = $this->getQuery($start_date, $end_date, $status);

          $stmt = $db->prepare($sql);
          $stmt->bindParam(':start_date', $start_date);
          $stmt->bindParam(':end_date', $end_date);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

          $time = time();

          $filename = $start_date."to".$end_date."-$time-transactions";
          $csv = new CsvReport(static::REPORT_PATH);
          $link = $csv->generateLink($filename, $result );

          return compact('csv_result', 'filename', 'link');
     }

     protected function getExportToCSV($data, $filename, $details) {

          $file = storage_path("app/public/csv/admin/transaction_report/".$filename);
          $fp = fopen($file, 'w+');
          if (count($data) > 0) {


               fputcsv ( $fp, array_keys ( $data [0] ));
               foreach($data as $row) {
                    $row = (array)$row;
                    fputcsv($fp, $row, ',', '"');
               }
               fclose ( $fp);

               $html = "<br><b><a style='margin-bottom:10px!important;' id='link-download' class='btn btn-primary flat' ";
               $html .= " href=''><span class='glyphicon glyphicon-th-list' aria-hidden='true'> ";
               $html .= " </span>&nbsp;&nbsp;$details</a></b> ";
          } else {

               $html = '<span>NO RESULTS!</span>';
          } 

          return $html;
     } 
	 
	 private function getQueryV2($start_date, $end_date, $status = "All")
	 {
		$sql = "
		SELECT * FROM v_cm_all_transactions WHERE commission_date BETWEEN DATE(:start_date) AND DATE(:end_date)
		
		"; // AND " . QueryHelper::NotExistsUnderBen('user_id') . "

		$start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('Y-m-d');
		$end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('Y-m-d');

		if(in_array($status, ['Approved', 'Declined', 'Error', 'Failed'])) {
				$sql .= " AND t_status = '$status' ";

				if($status == 'Approved') {
					$sql .= " AND EXISTS(SELECT 1 FROM v_cm_transactions tt WHERE tt.transaction_id = transaction_id AND DATE(tt.transaction_date) BETWEEN '$start_date' AND '$end_date')";
				}
		}

		return $sql;
	 }

     private function getQuery($start_date, $end_date, $status = "All")
     {
		//return $this->getQueryV2($start_date, $end_date, $status);
          $sql = "
               SELECT
                    a.id,
                    a.invoice,
                    a.purchaser,
                    a.sponsor,
                    a.product,
                    a.total_cv,
                    a.volume,
                    a.transaction_date,
                    a.amount_paid,
                    a.credited,
                    a.t_status,
                    a.order_type,
                    a.commission_type,
                    a.commission_period,
                    a.levelid,
                    a.level,
                    a.percent,
                    a.amount,
                    CASE 
                         WHEN a.amount = 0 AND a.gc_coupon > 0 AND a.ledger_payment = 0 THEN 'Gift Cards'
                         WHEN a.amount > 0 AND a.gc_coupon > 0 AND a.ledger_payment = 0 THEN 'Gift Cards + CC'
                         WHEN a.amount > 0 AND a.gc_coupon = 0 AND a.ledger_payment = 0 THEN 'CC'
                         WHEN a.amount = 0 AND a.gc_coupon = 0 AND a.ledger_payment > 0 THEN 'Ledger' 
                         WHEN a.amount = 0 AND a.gc_coupon > 0 AND a.ledger_payment > 0 THEN 'Ledger + Gift Cards' -- wala pa na implement 
                         WHEN a.amount > 0 AND a.gc_coupon > 0 AND a.ledger_payment > 0 THEN 'Ledger + Gift Cards + CC' -- wala pa na implement 
                         ELSE ''
                    END payment_type, 
                    a.phone,
                    a.gift_card,
                    a.coupon,
                    a.ledger_payment
                    
               FROM (
                    SELECT
                         t.id,
                         t.invoice,
                         CONCAT(u.fname, ' ', u.lname) AS purchaser,
                         u.levelid,
                         IFNULL(ccp.level, 0) AS level,
                         IFNULL(ccp.percent, 0) AS percent,
                         CONCAT(s.fname, ' ', s.lname) AS sponsor,
                         (
                              SELECT CONCAT(cp.start_date, '-', cp.end_date)
                              FROM cm_commission_payouts ccp 
                                   LEFT JOIN cm_commission_periods cp ON ccp.commission_period_id = cp.id 
                              WHERE ccp.transaction_id = t.id
                         )AS commission_period,
                         (
                              SELECT ct.name
                              FROM cm_commission_payouts ccp 
                                   LEFT JOIN cm_commission_periods cp ON ccp.commission_period_id = cp.id 
                                   LEFT JOIN cm_commission_types ct ON cp.commission_type_id = ct.id 
                              WHERE ccp.transaction_id = t.id
                         )AS commission_type,
                         (
                         SELECT GROUP_CONCAT(CONCAT(tp.quantity,' ',p.model) SEPARATOR ', ')
                         FROM transaction_products tp 
                         LEFT JOIN oc_product p ON p.product_id = tp.shoppingcart_product_id
                         WHERE tp.transaction_id = t.id
                         ) product,
                         getCommissionableVolume(t.id) AS total_cv,
                         getVolume(t.id) AS volume,
                         t.amount,
                         t.transactiondate AS transaction_date,
                         CONCAT('$',FORMAT(IFNULL(t.amount, 0), 2)) AS amount_paid,
                         t.credited AS credited,
                         t.status AS t_status,
                         CASE 
                         WHEN t.is_autoship = 1 
                              THEN 'Autoship'
                         WHEN EXISTS (SELECT 1 FROM oc_product oc WHERE EXISTS (SELECT 1 FROM transaction_products WHERE transaction_id = t.id AND shoppingcart_product_id = oc.product_id) AND oc.is_retail = 1 LIMIT 1)
                              THEN 'Retail'
                         ELSE 'Wholesale'
                         END AS order_type,
                         /*(CASE WHEN EXISTS(SELECT 1 FROM gift_cards WHERE transaction_id = t.id) THEN 'Gift Cards'
                         WHEN t.billmethod = 'CC' AND t.billmethod IS NOT NULL THEN 'CC' 
                         WHEN t.billmethod = 'Ledger' AND t.billmethod IS NOT NULL THEN 'Ledger'
                         WHEN t.billmethod = 'CC' AND EXISTS(SELECT 1 FROM gift_cards WHERE transaction_id = t.id) THEN 'Gift Cards + CC' END) AS payment_type,*/
                         '' payment_type,
                         (
                         SELECT IFNULL(SUM(och.amount), 0) * -1
                         FROM oc_coupon_history och
                         WHERE och.order_id = t.id
                         ) + (
                         SELECT IFNULL(SUM(gch.amount), 0) * -1
                         FROM gift_cards_history gch
                         WHERE gch.transaction_id = t.id
                         ) gc_coupon,
                         (
                         SELECT IFNULL(SUM(cl.amount), 0) * -1
                         FROM cm_ledger cl
                         WHERE cl.reference_number = t.id
                         AND cl.type = 'sale'
                         ) ledger_payment,
                         IFNULL(u.evephone,u.dayphone) AS phone,
                         t.status,
                         CONCAT('$', FORMAT(IFNULL(t.gift_card, 0), 2) ) AS gift_card,
                         (
                              SELECT GROUP_CONCAT(CONCAT(oco.`name`, ' - ', CONCAT('$', FORMAT(IFNULL(ABS(occh.amount), 0), 2) )))
                              FROM oc_coupon_history occh
                              JOIN oc_coupon oco ON occh.coupon_id = oco.coupon_id
                              WHERE occh.order_id = t.id
                         ) AS coupon
                    FROM transactions t
                    LEFT JOIN users u ON u.id = t.userid
                    LEFT JOIN users s ON s.id = t.sponsorid
                    LEFT JOIN cm_commission_payouts ccp ON t.id = ccp.transaction_id
                    WHERE 
                         t.type = 'product'
                         AND t.commission_date BETWEEN DATE(:start_date) AND DATE(:end_date)
                         AND " . $this->NotExistsUnderBen('t.userid') . "
               ) a
          ";

          $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('Y-m-d');
          $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('Y-m-d');

          if(in_array($status, ['Approved', 'Declined', 'Error', 'Failed'])) {
               $sql .= " WHERE a.status = '$status' ";

               if($status == 'Approved') {
                    $sql .= " AND EXISTS(SELECT 1 FROM v_cm_transactions tt WHERE tt.transaction_id = a.id AND DATE(tt.transaction_date) BETWEEN '$start_date' AND '$end_date')";
               }
          }

          return $sql;
     }

     private function getLineItemQuery($start_date,$end_date,$status="All")
     {
          $sql = "SELECT 
               t.id AS order_id,
               t.transactiondate AS date_of_sale,
               oc.sku AS sku_sold,
               tp.quantity AS quantity_sold,
               COALESCE(tp.tax, 0) AS sales_tax,
               (tp.total * tp.quantity) AS total 
               FROM
               transactions t 
               JOIN transaction_products tp ON tp.transaction_id = t.id
               JOIN oc_product oc ON oc.product_id = tp.shoppingcart_product_id
               WHERE t.type = 'product' AND DATE(t.transactiondate) BETWEEN DATE(:start_date) AND DATE(:end_date)
               AND " . $this->NotExistsUnderBen('t.userid');

          $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('Y-m-d');
          $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('Y-m-d');

          if(in_array($status, ['Approved', 'Declined', 'Error', 'Failed'])) {
               $sql .= " AND t.status = '$status' ";

               if($status == 'Approved') {
                    $sql .= " AND EXISTS(SELECT 1 FROM v_cm_transactions tt WHERE tt.transaction_id = t.id AND DATE(tt.transaction_date) BETWEEN '$start_date' AND '$end_date')";
               }
          }

          return $sql;
     }

     public function getLineItemReport($start_date, $end_date, $status = "All")
     {
          $db = DB::connection()->getPdo();

          $sql = $this->getLineItemQuery($start_date, $end_date, $status);

          $stmt = $db->prepare($sql);
          $stmt->bindParam(':start_date', $start_date);
          $stmt->bindParam(':end_date', $end_date);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          $time = time();
          $filename = $start_date."to".$end_date."-$time-line-item-report.csv";
          $details = "Download Line Item";
          $csv_result = $this->getExportLineItemCSV(json_decode(json_encode($result), true),$filename,$details);
          return compact('csv_result', 'filename');
     }

     protected function getExportLineItemCSV($data, $filename, $details) {

          $file = public_path("file/".$filename);
          $fp = fopen($file, 'w+');
          if (count($data) > 0) {


               fputcsv ( $fp, array_keys ( $data [0] ));
               foreach($data as $row) {
                    fputcsv($fp, $row, ',', '"');
               }
               fclose ( $fp);

     //            $report_link = strtolower('/public/file/' . $filename);
               $html = "<br><b><a style='margin-bottom:10px!important;' id='link-download' class='btn btn-primary flat' ";
               $html .= " href=''><span class='glyphicon glyphicon-th-list' aria-hidden='true'> ";
               $html .= " </span>&nbsp;&nbsp;$details</a></b> ";
          } else {

               $html = '<span>NO RESULTS!</span>';
          } /* (count($data) > 0) */

          return $html;
     } /* getExportToCSV */

     private function getTransactionLevelQuery($start_date,$end_date,$status = "All")
     {
          $sql = "SELECT 
                    t.id AS order_id,
                    t.transactiondate AS date_of_transaction,
                    t.sub_total,
                    t.shipping_fee AS shipping,
                    COALESCE(t.tax, 0) AS sales_tax,
                    (
                         SELECT IFNULL(GROUP_CONCAT(CONCAT(oco.`name`, ' - ', CONCAT('$', FORMAT(IFNULL(ABS(occh.amount), 0), 2) ))),'$0.00')
                         FROM oc_coupon_history occh
                         JOIN oc_coupon oco ON occh.coupon_id = oco.coupon_id
                         WHERE occh.order_id = t.id
                    ) AS coupon_discount,
                    CONCAT('$',FORMAT(SUM(IFNULL(t.sub_total, 0) + IFNULL(t.shipping_fee, 0) + IFNULL(t.tax, 0)),2)) AS grand_total,
                    CONCAT('$', FORMAT(IFNULL(t.gift_card, 0), 2) ) AS gift_card
               FROM transactions t
               WHERE t.type = 'product' AND DATE(t.transactiondate) BETWEEN DATE(:start_date) AND DATE(:end_date)
               AND " . $this->NotExistsUnderBen('t.userid'). " GROUP BY t.id";

          $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->format('Y-m-d');
          $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->format('Y-m-d');

          if(in_array($status, ['Approved', 'Declined', 'Error', 'Failed'])) {
               $sql .= " AND t.status = '$status' ";

               if($status == 'Approved') {
                    $sql .= " AND EXISTS(SELECT 1 FROM v_cm_transactions tt WHERE tt.transaction_id = t.id AND DATE(tt.transaction_date) BETWEEN '$start_date' AND '$end_date')";
               }
          }

          return $sql;
     }

     public function getTransactionLevelReport($start_date, $end_date, $status = "All")
     {
          $db = DB::connection()->getPdo();

          $sql = $this->getTransactionLevelQuery($start_date, $end_date, $status);

          $stmt = $db->prepare($sql);
          $stmt->bindParam(':start_date', $start_date);
          $stmt->bindParam(':end_date', $end_date);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          $time = time();
          $filename = $start_date."to".$end_date."-$time-transaction-level-report.csv";
          $details = "Download Transaction Level";
          $csv_result = $this->getExportTransactionLevelCSV(json_decode(json_encode($result), true),$filename,$details);
          return compact('csv_result', 'filename');
     }

     protected function getExportTransactionLevelCSV($data, $filename, $details) {

          $file = public_path("file/".$filename);
          $fp = fopen($file, 'w+');
          if (count($data) > 0) {


               fputcsv ( $fp, array_keys ( $data [0] ));
               foreach($data as $row) {
                    fputcsv($fp, $row, ',', '"');
               }
               fclose ( $fp);

     //            $report_link = strtolower('/public/file/' . $filename);
               $html = "<br><b><a style='margin-bottom:10px!important;' id='link-download' class='btn btn-primary flat' ";
               $html .= " href=''><span class='glyphicon glyphicon-th-list' aria-hidden='true'> ";
               $html .= " </span>&nbsp;&nbsp;$details</a></b> ";
          } else {

               $html = '<span>NO RESULTS!</span>';
          } /* (count($data) > 0) */

          return $html;
     } /* getExportToCSV */
}