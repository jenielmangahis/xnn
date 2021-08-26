DROP PROCEDURE IF EXISTS sp_generateAffiliateDashboardReport;
DELIMITER //
CREATE PROCEDURE sp_generateAffiliateDashboardReport(
	IN p_user_id INT(11)
)
BEGIN
	DECLARE v_currentDay ENUM(
							 'Monday'
							,'Tuesday'
							,'Wednesday'
							,'Thursday'
							,'Friday'
							,'Saturday'
							,'Sunday') DEFAULT 'Monday';
	DECLARE v_currentDate VARCHAR(10) DEFAULT '';
	DECLARE v_json TEXT;
	DECLARE v_idx INT(11) DEFAULT 0;
	DECLARE done INT(11) DEFAULT FALSE;
	/* fetch_checkmatch */
	DECLARE v_is_qualified ENUM('Yes', 'No') DEFAULT 'No';
	DECLARE v_date_until VARCHAR(10) DEFAULT '';
	/* fetch_week_snapshot */
	DECLARE v_id  INT(11) DEFAULT 0;
	DECLARE v_mon DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_tue DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_wed DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_thu DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_fri DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_sat DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_sun DECIMAL(11, 2) DEFAULT 0.00;
	/* fetch_lifetime_earning */
	DECLARE ith_user_id INT(11) DEFAULT 0;
	DECLARE ith_amount DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_name VARCHAR(256) DEFAULT '';
	/* fetch_commission_history */
	DECLARE v_seller VARCHAR(256) DEFAULT '';
	DECLARE v_soldto VARCHAR(256) DEFAULT '';
	DECLARE v_product VARCHAR(256) DEFAULT '';
	DECLARE v_date_purchase VARCHAR(256) DEFAULT '';
	DECLARE v_commission_type VARCHAR(256) DEFAULT 0;
	DECLARE v_sales_count INT(11) DEFAULT 0;
	DECLARE v_amount DECIMAL(11,2) DEFAULT 0.00;

	/*
	The sum of all locked commissions for that member for all time 
	for all commission types in the cm_commission_payouts table.*/
	DECLARE fetch_lifetime_earning CURSOR FOR 
		SELECT
			 list.user_id 				AS table_1_user_id
			,SUM(list.amount) 			AS table_1_amount
		FROM (
			SELECT
				 cp.user_id 					AS user_id
				,SUM(cp.`value`) 				AS amount
			FROM cm_commission_payouts 			AS cp
			INNER JOIN cm_commission_periods 	AS cpd ON (cp.commission_period_id = cpd.commission_period_id)
			WHERE
				(cp.user_id = p_user_id) AND 
				(cpd.locked = 1)
			GROUP BY cp.user_id
			UNION
			SELECT
				 0 AS user_id
				,0 AS amount
		) AS list;
	/*
	The sum of all UNLOCKED commissions from all commission 
	types and all time. */
	DECLARE fetch_pending_commission CURSOR FOR 
		SELECT
			 list.user_id 		AS table_2_user_id
			,SUM(list.amount) 	AS table_2_amount
		FROM (
			SELECT
				 cp.user_id AS user_id
				,SUM(cp.`value`) AS amount
			FROM cm_commission_payouts AS cp
			INNER JOIN cm_commission_periods AS cpd ON (cp.commission_period_id = cpd.commission_period_id)
			WHERE
				(cp.user_id = p_user_id) AND 
				(cpd.locked = 0)
			GROUP BY cp.user_id
			UNION
			SELECT
				 0 AS user_id
				,0 AS amount
		) AS list;
	/* 
	Daily part commissions.
	Getting all payouts belong to unlocked periods.
	NOTE:
		Do not include 1004;
		Do not include 1005;
		Here we do not include 1004 and 1005 the instructions is to exclude this products
		during the implementations.*/
	DECLARE fetch_commission_history CURSOR FOR
		SELECT
			 CONCAT(seller.fname, ' ', seller.lname) 		AS seller
			,CONCAT(soldto.fname, ' ', soldto.lname) 		AS soldto
			,sp.`name` 								 		AS product
			,CONCAT(
				DATE_FORMAT(trn.transactiondate, '%M %d, %Y')
				,' '
				,DAYNAME(trn.transactiondate)
			)  												AS date_purchase
			,cpt.description 						 		AS commission_type
			,co.sales_count 						 		AS sales_count
			,FORMAT(cpd.`value`, 2) 						AS amount
		FROM cm_commission_payout_details 		AS cpd
		INNER JOIN cm_commission_payouts 		AS cp 		ON (cp.commission_payout_id = cpd.commission_payout_id)
		INNER JOIN cm_commission_orders 		AS co 		ON (co.id = cpd.commission_order_id)
		INNER JOIN transactions 				AS trn 		ON (trn.id = co.shopping_cart_id)
		INNER JOIN shoppingcart_products 		AS sp 		ON (sp.id = trn.itemid)
		INNER JOIN users 						AS seller 	ON (seller.id = co.seller_id)
		INNER JOIN users 						AS soldto 	ON (soldto.id = co.sold_to_id)
		INNER JOIN cm_commission_payout_types 	AS cpt		ON (cpt.commission_payout_type_id = co.commission_type)
		INNER JOIN cm_commission_periods		AS cps		ON (cps.commission_period_id = cp.commission_period_id)
		WHERE (cp.user_id = p_user_id) AND
			  ((sp.id <> 1004) AND (sp.id <> 1005)) AND
			  (cps.locked = 0);
	/* 
	Get list of product and the current sales count base of the selected user. */
	DECLARE fetch_sales_count CURSOR FOR
		SELECT
			 sp.id
			,CONCAT(sp.id, '-', sp.`name`) AS `name`
			,sp_getSalesCount(p_user_id, sp.id) AS sales_count
		FROM shoppingcart_products AS sp
		WHERE (sp.id <> 1004) AND 
			  (sp.id <> 1005)
		ORDER BY sp.id ASC;
	DECLARE fetch_checkmatch CURSOR FOR
		SELECT 
			 cm.is_qualified		AS is_qualified
			,MAX(details.date_to) 	AS date_to
		FROM cm_checkmatch AS cm
		INNER JOIN (
			SELECT
				 cd.checkmatch_id
				,cd.date_to
			FROM cm_checkmatch_details AS cd
		) AS details ON (details.checkmatch_id = cm.id)
		WHERE (cm.user_id = p_user_id);
	DECLARE fetch_week_snapshot CURSOR FOR 
		SELECT
			 SUM(CASE WHEN (tws.day_name = 'Monday') 	THEN tws.amount ELSE 0 END) AS mon
			,SUM(CASE WHEN (tws.day_name = 'Tuesday') 	THEN tws.amount ELSE 0 END) AS tue
			,SUM(CASE WHEN (tws.day_name = 'Wednesday') THEN tws.amount ELSE 0 END) AS wed
			,SUM(CASE WHEN (tws.day_name = 'Thursday') 	THEN tws.amount ELSE 0 END) AS thu
			,SUM(CASE WHEN (tws.day_name = 'Friday') 	THEN tws.amount ELSE 0 END) AS fri
			,SUM(CASE WHEN (tws.day_name = 'Saturday') 	THEN tws.amount ELSE 0 END) AS sat
			,SUM(CASE WHEN (tws.day_name = 'Sunday') 	THEN tws.amount ELSE 0 END) AS sun
		FROM temp_week_snapshot AS tws;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=TRUE;

	PROC: BEGIN

	/*
	Do not continue of the current process not finished. 
	The caller of this procedure may call this many times but once the current
	execution is not finish we do not allow another call to continue to prevent
	duplicate un-necessary result. */
	IF (sp_get_flag('GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY') = 'TRUE') THEN
		CALL echo(CONCAT('sp_generateAffiliateDashboardReport is invoke while it is busy.'));
		SET v_json = '';
		SET v_json = CONCAT(v_json, '{');
		SET v_json = CONCAT(v_json, '    "user_id" : ', p_user_id, '');
		SET v_json = CONCAT(v_json, '   ,"time_processed" : "', NOW(), '"');
		SET v_json = CONCAT(v_json, '   ,"is_successful" : False');
		SET v_json = CONCAT(v_json, '}');
		SELECT v_json;
		LEAVE PROC;
	END IF;

	/* Set the flag to denote that this procedure is busy. */
	CALL sp_set_flag('GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY', 'TRUE');	

	CREATE TABLE temp_week_snapshot (
		 day_name ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
		,amount DECIMAL(14,2) DEFAULT 0.00
		,amount_date VARCHAR(10) DEFAULT ''
		,PRIMARY KEY (day_name)
	);

	/* ---------------------------------------------------------------------------------------
	7 Day Snapshot.
	--------------------------------------------------------------------------------------- */
	SET v_currentDate =  DATE_FORMAT(NOW(), '%Y-%m-%d');
	SET v_currentDay = DAYNAME(v_currentDate);
	IF (v_currentDay = 'Sunday') THEN
		SET v_currentDate = DATE_ADD(v_currentDate, INTERVAL -1 DAY);
	END IF;
	WHILE (v_currentDay <> 'Sunday') DO
		SET v_currentDate = DATE_ADD(v_currentDate, INTERVAL -1 DAY);
		SET v_currentDay = DAYNAME(v_currentDate);
	END WHILE;

	/* From the last day sunday travel until reach monday(the first day of the week.) */
	SET v_currentDay = DAYNAME(v_currentDate);
	loopWhile: BEGIN
	WHILE (TRUE) DO
		SET v_amount = sp_getTotalPayoutByDatePerAffiliate(p_user_id, v_currentDate);
		INSERT INTO temp_week_snapshot VALUES (v_currentDay, v_amount, v_currentDate);
		SET v_currentDate = DATE_ADD(v_currentDate, INTERVAL -1 DAY);
		SET v_currentDay = DAYNAME(v_currentDate);
		IF (v_currentDay = 'Sunday') THEN
			LEAVE loopWhile;
		END IF;
	END WHILE;
	END loopWhile;
	
	/* ---------------------------------------------------------------------------------------
	Form a json format of the result tables.
	------------------------------------------------------------------------------------------ */
	SET v_json = '{';
	SET v_json = CONCAT(v_json, '  "user_id" : ', p_user_id, '');
	SET v_json = CONCAT(v_json, ' ,"time_processed" : "', NOW(), '"');
	SET v_json = CONCAT(v_json, ' ,"is_successful" : true');	

	/* Checkmatch */
	OPEN fetch_checkmatch;
	SET v_idx = 0;
	SET v_json = CONCAT(v_json, ', "checkmatch": [');
	loop_i: LOOP
		FETCH fetch_checkmatch INTO v_is_qualified, v_date_until;
		IF (done) THEN
			SET done = FALSE;
			LEAVE loop_i;
		END IF;
		IF (v_idx = 0) THEN
			SET v_json = CONCAT(v_json, '{');
			SET v_json = CONCAT(v_json, ' "is_qualified" : "', v_is_qualified, '"');
			SET v_json = CONCAT(v_json, ',"date_until" : "', DATE_FORMAT(v_date_until, '%M %d, %Y'), '"');
			SET v_json = CONCAT(v_json, '}');
		ELSE
			SET v_json = CONCAT(v_json, ',{');
			SET v_json = CONCAT(v_json, ' "is_qualified" : "', v_is_qualified, '"');
			SET v_json = CONCAT(v_json, ',"date_until" : "', DATE_FORMAT(v_date_until, '%M %d, %Y'), '"');
			SET v_json = CONCAT(v_json, '}');
		END IF;
	END LOOP loop_i;
	CLOSE fetch_checkmatch;
	SET v_json = CONCAT(v_json, ']');
	
	/* Total earning. */
	SET v_idx = 0;
	SET v_json = CONCAT(v_json, ', "total_earning": [');	
	OPEN fetch_lifetime_earning;
	loop_i: LOOP
		FETCH fetch_lifetime_earning INTO
			 ith_user_id
			,ith_amount;
		IF (done) THEN
			SET done = FALSE;
			LEAVE loop_i;
		END IF;
		IF (v_idx = 0) THEN
			SET v_json = CONCAT(v_json, '{');
			SET v_json = CONCAT(v_json, ' "user_id" : ', ith_user_id);
			SET v_json = CONCAT(v_json, ',"amount" : "', CONCAT('$', FORMAT(ith_amount, 2)), '"');
			SET v_json = CONCAT(v_json, '}');
		ELSE 
			SET v_json = CONCAT(v_json, ',{');
			SET v_json = CONCAT(v_json, ' "user_id" : ', ith_user_id);
			SET v_json = CONCAT(v_json, ',"amount" : "', CONCAT('$', FORMAT(ith_amount, 2)), '"');
			SET v_json = CONCAT(v_json, '}');
		END IF;
	END LOOP loop_i;
	CLOSE fetch_lifetime_earning;
	SET v_json = CONCAT(v_json, ']');
	
	/* Total pending commission. */
	SET v_json = CONCAT(v_json, ',"total_pending_commission": [');
	SET v_idx = 0;
	OPEN fetch_pending_commission;
	loop_i: LOOP
		FETCH fetch_pending_commission INTO
			 ith_user_id
			,ith_amount;
		IF (done) THEN
			SET done = FALSE;
			LEAVE loop_i;
		END IF;
		IF (v_idx = 0) THEN
			SET v_json = CONCAT(v_json, '{ "user_id" : ', ith_user_id,' , "amount" : "', CONCAT('$', FORMAT(ith_amount, 2)), '" }');
		ELSE 
			SET v_json = CONCAT(v_json, ',{ "user_id" : ', ith_user_id,' , "amount" : "', CONCAT('$', FORMAT(ith_amount, 2)), '" }');
		END IF;
	END LOOP loop_i;
	CLOSE fetch_pending_commission;	
	SET v_json = CONCAT(v_json, ']');

	/* Commission history. */
	SET v_json = CONCAT(v_json, ',"commission_history": [');
	SET v_idx = 0;
	OPEN fetch_commission_history;
	loop_i: LOOP
		FETCH fetch_commission_history INTO 
				 v_seller
				,v_soldto
				,v_product
				,v_date_purchase
				,v_commission_type
				,v_sales_count
				,v_amount;
		IF (done) THEN
			SET done = FALSE;
			LEAVE loop_i;
		END IF;
		IF (v_idx = 0) THEN
			SET v_json = CONCAT(v_json, '{');
			SET v_json = CONCAT(v_json, '"seller" : "', v_seller,'"');
			SET v_json = CONCAT(v_json, ', "soldto" : "', v_soldto,'"');
			SET v_json = CONCAT(v_json, ', "product" : "', v_product,'"');
			SET v_json = CONCAT(v_json, ', "date_purchase": "', v_date_purchase, '"');
			SET v_json = CONCAT(v_json, ', "commission_type": "',  v_commission_type, '"');
			SET v_json = CONCAT(v_json, ', "sales_count": ', v_sales_count);
			SET v_json = CONCAT(v_json, ', "amount": "', CONCAT('$', FORMAT(v_amount, 2)),'"');
			SET v_json = CONCAT(v_json, '}');
		ELSE
			SET v_json = CONCAT(v_json, ',{');
			SET v_json = CONCAT(v_json, '"seller" : "', v_seller,'"');
			SET v_json = CONCAT(v_json, ', "soldto" : "', v_soldto,'"');
			SET v_json = CONCAT(v_json, ', "product" : "', v_product,'"');
			SET v_json = CONCAT(v_json, ', "date_purchase": "', v_date_purchase, '"');
			SET v_json = CONCAT(v_json, ', "commission_type": "',  v_commission_type, '"');
			SET v_json = CONCAT(v_json, ', "sales_count": ', v_sales_count);
			SET v_json = CONCAT(v_json, ', "amount": "', CONCAT('$', FORMAT(v_amount, 2)),'"');
			SET v_json = CONCAT(v_json, '}');
		END IF;
		SET v_idx = v_idx + 1;
	END LOOP loop_i;
	CLOSE fetch_commission_history;
	SET v_json = CONCAT(v_json, '] ');

	/* Sales count. */
	SET v_json = CONCAT(v_json, ',"sales_count": [');
	SET v_idx = 0;
	OPEN fetch_sales_count;
	loop_i: LOOP 
		FETCH fetch_sales_count INTO 
			 ith_user_id
			,v_name
			,v_sales_count;
		IF (done) THEN
			SET done = FALSE;
			LEAVE loop_i;
		END IF;
		IF (v_idx = 0) THEN
			SET v_json = CONCAT(v_json, '{');
			SET v_json = CONCAT(v_json, ' "sku": ', ith_user_id);
			SET v_json = CONCAT(v_json, ',"product_name": "', v_name, '"');
			SET v_json = CONCAT(v_json, ',"count": ', v_sales_count);
			SET v_json = CONCAT(v_json, '}');
		ELSE
			SET v_json = CONCAT(v_json, ',{');
			SET v_json = CONCAT(v_json, ' "sku": ', ith_user_id);
			SET v_json = CONCAT(v_json, ',"product_name": "', v_name, '"');
			SET v_json = CONCAT(v_json, ',"count": ', v_sales_count);
			SET v_json = CONCAT(v_json, '}');
		END IF;
		SET v_idx = v_idx + 1;		
	END LOOP loop_i;
	CLOSE fetch_sales_count;
	SET v_json = CONCAT(v_json, '] ');

	/* Weekly snapshot. */
	SET v_json = CONCAT(v_json, ',"weekly_snapshot":');
	SET v_json = CONCAT(v_json, '[');
	SET v_idx = 0;
	OPEN fetch_week_snapshot;
	loop_i: LOOP 
		FETCH fetch_week_snapshot INTO 
			  v_mon
			, v_tue
			, v_wed
			, v_thu
			, v_fri
			, v_sat
			, v_sun;
		IF (done) THEN
			SET done = FALSE;
			LEAVE loop_i;
		END IF;
		IF (v_idx = 0) THEN
			SET v_json = CONCAT(v_json, '{');
			SET v_json = CONCAT(v_json, ' "mon": "', IF(v_mon <> 0, CONCAT('$', FORMAT(v_mon, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"tue": "', IF(v_tue <> 0, CONCAT('$', FORMAT(v_tue, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"wed": "', IF(v_wed <> 0, CONCAT('$', FORMAT(v_wed, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"thu": "', IF(v_thu <> 0, CONCAT('$', FORMAT(v_thu, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"fri": "', IF(v_fri <> 0, CONCAT('$', FORMAT(v_fri, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"sat": "', IF(v_sat <> 0, CONCAT('$', FORMAT(v_sat, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"sun": "', IF(v_sun <> 0, CONCAT('$', FORMAT(v_sun, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, '}');
		ELSE
			SET v_json = CONCAT(v_json, ',');
			SET v_json = CONCAT(v_json, '{');
			SET v_json = CONCAT(v_json, ' "mon": "', IF(v_mon <> 0, CONCAT('$', FORMAT(v_mon, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"tue": "', IF(v_tue <> 0, CONCAT('$', FORMAT(v_tue, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"wed": "', IF(v_wed <> 0, CONCAT('$', FORMAT(v_wed, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"thu": "', IF(v_thu <> 0, CONCAT('$', FORMAT(v_thu, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"fri": "', IF(v_fri <> 0, CONCAT('$', FORMAT(v_fri, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"sat": "', IF(v_sat <> 0, CONCAT('$', FORMAT(v_sat, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, ',"sun": "', IF(v_sun <> 0, CONCAT('$', FORMAT(v_sun, 2)), '$0.00') , '"');
			SET v_json = CONCAT(v_json, '}');
		END IF;

		SET v_idx = v_idx + 1;
	END LOOP loop_i;
	CLOSE fetch_week_snapshot;
	SET v_json = CONCAT(v_json, ']');
	SET v_json = CONCAT(v_json, '}');

	/* Delete the temporary tables. */
	DROP TABLE temp_week_snapshot;

	/* Reset the flag to denote that this procedure is not busy. */
	CALL sp_set_flag('GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY', 'FALSE');
	
	/* Return json format of the data. */
	SELECT v_json;

	END PROC;

END //
DELIMITER ;