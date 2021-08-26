DROP PROCEDURE IF EXISTS sp_generate_historical_comm_rpt;
DELIMITER //
CREATE PROCEDURE sp_generate_historical_comm_rpt(
	 IN p_commission_period_type_id INT(11)
	,IN p_commission_period_id INT(11)
	,IN p_date_from VARCHAR(30)
	,IN p_date_to VARCHAR(30)
)
BEGIN
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE v_commissionPeriodId INT(11) DEFAULT 0;
	DECLARE v_startDate VARCHAR(10) DEFAULT '';
	DECLARE v_endDate VARCHAR(10) DEFAULT '';
	DECLARE fetchDailyPeriods CURSOR FOR
		SELECT
			 ccp.commission_period_id
			,ccp.start_date
			,ccp.end_date
		FROM cm_commission_periods AS ccp
		WHERE (ccp.commission_period_type_id = p_commission_period_type_id) AND
			  (ccp.locked = 1);
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;

	PROC: BEGIN

	IF (p_commission_period_id > 0) THEN
		
		SELECT
			 usr.lname 										AS `Lastname`
			,usr.fname 										AS `Firstname`
			,scp.name										AS `Product`
			,cpd.amount										AS `Commission`
			,DATE_FORMAT(trn.transactiondate, '%M %d, %Y') 	AS `DatePurchase`
			,cco.sales_count								AS `SalesCount`
		FROM cm_commission_payout_details 	AS cpd
		INNER JOIN cm_commission_payouts 	AS ccp ON (ccp.commission_payout_id = cpd.commission_payout_id)
		INNER JOIN transactions 			AS trn ON (trn.id = cpd.order_id)
		INNER JOIN cm_commission_orders 	AS cco ON (cco.id = cpd.commission_order_id)
		INNER JOIN shoppingcart_products 	AS scp ON (scp.id = trn.itemid)
		INNER JOIN users 					AS usr ON (usr.id = cpd.user_id)
		WHERE (ccp.commission_payout_type_id = p_commission_period_type_id) AND 
			  (ccp.commission_period_id = p_commission_period_id);

	ELSEIF (p_commission_period_id < 0) THEN

		SET @ids = '-1';
		OPEN fetchDailyPeriods;
		loop_i: LOOP
			FETCH fetchDailyPeriods INTO 
				 v_commissionPeriodId
				,v_startDate
				,v_endDate;
			IF (v_done) THEN
				SET v_done=FALSE;
				LEAVE loop_i;
			END IF;
			IF ((v_startDate >= p_date_from) OR (v_endDate <= p_date_to)) THEN
				SET @ids = CONCAT(@ids, ', ', v_commissionPeriodId);
			END IF;
		END LOOP loop_i;
		CLOSE fetchDailyPeriods;

		SET @query = CONCAT(
			'SELECT
				 usr.lname 				AS `Lastname`
				,usr.fname 				AS `Firstname`
				,scp.name				AS `Product`
				,cpd.amount				AS `Commission`
				,trn.transactiondate 	AS `DatePurchase`
				,cco.sales_count		AS `SalesCount`
			FROM cm_commission_payout_details 	AS cpd
			INNER JOIN cm_commission_payouts 	AS ccp ON (ccp.commission_payout_id = cpd.commission_payout_id)
			INNER JOIN transactions 			AS trn ON (trn.id = cpd.order_id)
			INNER JOIN cm_commission_orders 	AS cco ON (cco.id = cpd.commission_order_id)
			INNER JOIN shoppingcart_products 	AS scp ON (scp.id = trn.itemid)
			INNER JOIN users 					AS usr ON (usr.id = cpd.user_id)
			WHERE (ccp.commission_payout_type_id = ', p_commission_period_type_id, ') AND
				  (ccp.commission_period_id IN (', @ids, '))');
		PREPARE sql_statement FROM @query;
		EXECUTE sql_statement;
		DEALLOCATE PREPARE sql_statement;

	END IF;

	END PROC;

END //
DELIMITER ;