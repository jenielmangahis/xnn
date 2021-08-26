DROP PROCEDURE IF EXISTS sp_savePayoutDetailsList;
DELIMITER //
CREATE PROCEDURE sp_savePayoutDetailsList(
	 IN p_commission_period_id INT(11)	-- 
	,IN p_commissionPayoutId INT(11)	-- 
	,IN p_user_id INT(11)				-- 
	,IN p_commissionType INT(11)		-- 
	,IN p_dateFrom VARCHAR(10)			-- 2016-05-01
	,IN p_dateTo VARCHAR(10)			-- 2016-05-01
	,IN p_total DECIMAL(11,2)			-- 0.00
)
BEGIN
	/*
		Pre-condition:
			p_total must not zero.
	*/
	DECLARE RETAIL INT(11) DEFAULT 			1; -- Retail Commissions
	DECLARE RETAIL_POOL INT(11) DEFAULT 	2; -- Retail Pool
	DECLARE CODED_COMM INT(11) DEFAULT 		3; -- Coded Commissions
	DECLARE CHECKMATCH_COMM INT(11) DEFAULT 4; -- Check Match Commissions
	DECLARE BARRY_COMM INT(11) DEFAULT 		5; -- Barry's Commissions
	
	DECLARE v_Idx INT(11) DEFAULT 0;
	DECLARE v_Count INT(11) DEFAULT 0;
	DECLARE v_AveAmount DECIMAL(11, 2) DEFAULT 0.00;
	DECLARE v_AveAcc DECIMAL(11,2) DEFAULT 0.00;
	
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE v_ithId INT(11) DEFAULT 0;
	DECLARE v_ithSellerId INT(11) DEFAULT 0;
	DECLARE v_ithPassupSponsorId INT(11) DEFAULT 0;
	DECLARE v_ithSoldToId INT(11) DEFAULT 0;
	DECLARE v_ithShoppingCartId INT(11) DEFAULT 0;
	DECLARE v_ithSalesCount INT(11) DEFAULT 0;
	DECLARE v_ithCommissionType INT(11) DEFAULT 0;
	DECLARE v_ithCommissionPercentage DECIMAL(11, 2) DEFAULT 0;
	DECLARE v_ithNotIncluded INT(11) DEFAULT 0;
	DECLARE v_ithCv DECIMAL(11,2) DEFAULT 0.00;
	DECLARE v_ithCmv DECIMAL(11,2) DEFAULT 0.00;
	DECLARE v_ithCd DECIMAL(11,2) DEFAULT 0.00;
	DECLARE fetchOrders CURSOR FOR
		SELECT
			 cm_commission_orders.id AS id
			,cm_commission_orders.seller_id AS seller_id
			,cm_commission_orders.passup_sponsor_id AS passup_sponsor_id
			,cm_commission_orders.sold_to_id AS sold_to_id
			,cm_commission_orders.shopping_cart_id AS shopping_cart_id
			,cm_commission_orders.sales_count AS sales_count
			,cm_commission_orders.commission_type AS commission_type
			,cm_commission_orders.commission_percentage AS commission_percentage
			,cm_commission_orders.not_included AS not_included
			,shoppingcart_products.cv AS cv
			,shoppingcart_products.cmv AS cmv
			,shoppingcart_products.cd AS cd
		FROM cm_commission_orders
		INNER JOIN transactions ON (transactions.id = cm_commission_orders.shopping_cart_id)
		INNER JOIN shoppingcart_products ON (shoppingcart_products.id = transactions.itemid)
		WHERE (cm_commission_orders.seller_id = p_user_id) AND 
			  (cm_commission_orders.commission_type = p_commissionType) AND 
			  ((DATE_FORMAT(transactions.transactiondate, '%Y-%m-%d') >= p_dateFrom) AND (DATE_FORMAT(transactions.transactiondate, '%Y-%m-%d') <= p_dateTo)) AND
			  (cm_commission_orders.id NOT IN (
					SELECT
						cm_commission_payout_refund.order_id
					FROM cm_commission_payout_refund
					WHERE
						(cm_commission_payout_refund.commission_payout_id = 0) AND
						(cm_commission_payout_refund.amount = 0) AND
						(cm_commission_payout_refund.is_paid = 0)
			  ));
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
	
	PROC: BEGIN
	
	IF (p_commissionType = RETAIL_POOL) THEN
		--
		-- Retail pool.
		--
		SET v_Count = COALESCE((
							SELECT COUNT(*) FROM cm_commission_orders
							INNER JOIN transactions ON (transactions.id = cm_commission_orders.shopping_cart_id)
							INNER JOIN shoppingcart_products ON (shoppingcart_products.id = transactions.itemid)
							WHERE (cm_commission_orders.seller_id = p_user_id) AND 
								  (cm_commission_orders.commission_type = p_commissionType) AND 
								  ((DATE_FORMAT(transactions.transactiondate, '%Y-%m-%d') >= p_dateFrom) AND 
								  (DATE_FORMAT(transactions.transactiondate, '%Y-%m-%d') <= p_dateTo)))
							, 0);
		SET v_AveAmount = p_total / v_Count;
		SET v_Idx = 1;
		SET v_AveAcc = 0.00;
		
		OPEN fetchOrders;
		loop_i: LOOP
			FETCH fetchOrders INTO
				 v_ithId
				,v_ithSellerId
				,v_ithPassupSponsorId
				,v_ithSoldToId
				,v_ithShoppingCartId
				,v_ithSalesCount
				,v_ithCommissionType
				,v_ithCommissionPercentage
				,v_ithNotIncluded
				,v_ithCv
				,v_ithCmv
				,v_ithCd;
			IF v_done THEN
				SET v_done = FALSE;
				LEAVE loop_i;
			END IF;
			
			IF (v_Idx = v_Count) THEN
				SET v_AveAmount = p_total - v_AveAcc;
			END IF;
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @p_commission_payout_id = 0; -- Primary key generated inside.
			SET @commissionType = RETAIL_POOL;
			SET @userId = v_ithSellerId;
			SET @commissionPeriodId = p_commission_period_id;
			SET @level = 0;
			SET @value = v_AveAmount;
			SET @isPaid = 0;
			CALL sp_save_cm_commission_payouts(
				 @rowsAffected 				-- OUT p_rows_affected INT(11)
				,@operations 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_commission_payout_id 	-- INOUT p_commission_payout_id INT(10)
				,@commissionType 			-- IN p_commission_payout_type_id INT(11)
				,@userId 					-- IN p_user_id INT(11)
				,@commissionPeriodId 		-- IN p_commission_period_id INT(11)
				,@level 					-- IN p_level INT(11)
				,@value 					-- IN p_value VARCHAR(1048)
				,@isPaid 					-- IN p_is_paid INT(11)
			);			
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @newKey = 0;
			SET @commissionPayoutId = @p_commission_payout_id;
			SET @orderId = v_ithShoppingCartId;
			SET @userId = v_ithSoldToId;
			SET @level = 0;
			SET @value = v_AveAmount;
			SET @percent = 1;
			SET @amount = v_AveAmount;
			SET @commissionOrderId = v_ithId;
			CALL sp_save_cm_commission_payouts_details(
				 @rowsAffected 			-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 				-- INOUT p_commission_payout_detail_id INT(10)
				,@commissionPayoutId 	-- IN p_commission_payout_id INT(11)
				,@orderId 				-- IN p_order_id INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@level 				-- IN p_level INT(11)
				,@value 				-- IN p_value VARCHAR(1024)
				,@percent 				-- IN p_percent DECIMAL(4,1)
				,@amount 				-- IN p_amount VARCHAR(1024)
				,@commissionOrderId 	-- IN p_commission_order_id INT(11)
			);
			
			SET v_AveAcc = v_AveAcc + v_AveAmount;
			SET v_Idx = v_Idx + 1;
		END LOOP loop_i;
		CLOSE fetchOrders;
	ELSEIF (p_commissionType = BARRY_COMM) THEN
		--
		-- Barry commission.
		--
		OPEN fetchOrders;
		loop_i: LOOP
			FETCH fetchOrders INTO
				 v_ithId
				,v_ithSellerId
				,v_ithPassupSponsorId
				,v_ithSoldToId
				,v_ithShoppingCartId
				,v_ithSalesCount
				,v_ithCommissionType
				,v_ithCommissionPercentage
				,v_ithNotIncluded
				,v_ithCv
				,v_ithCmv
				,v_ithCd;
			IF v_done THEN
				SET v_done = FALSE;
				LEAVE loop_i;
			END IF;
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @p_commission_payout_id = 0; -- Primary key generated inside.
			SET @commissionType = BARRY_COMM;
			SET @userId = sp_GLOBAL('BARRY');
			SET @commissionPeriodId = p_commission_period_id;
			SET @level = 0;
			SET @value = 3.00;
			SET @isPaid = 0;
			CALL sp_save_cm_commission_payouts(
				 @rowsAffected 				-- OUT p_rows_affected INT(11)
				,@operations 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_commission_payout_id 	-- INOUT p_commission_payout_id INT(10)
				,@commissionType 			-- IN p_commission_payout_type_id INT(11)
				,@userId 					-- IN p_user_id INT(11)
				,@commissionPeriodId 		-- IN p_commission_period_id INT(11)
				,@level 					-- IN p_level INT(11)
				,@value 					-- IN p_value VARCHAR(1048)
				,@isPaid 					-- IN p_is_paid INT(11)
			);			
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @newKey = 0;
			SET @commissionPayoutId = @p_commission_payout_id;
			SET @orderId = v_ithShoppingCartId;
			SET @userId = v_ithSoldToId;
			SET @level = 0;
			SET @value = 2.00;
			SET @percent = 1;
			SET @amount = 2.00;
			SET @commissionOrderId = v_ithId;
			CALL sp_save_cm_commission_payouts_details(
				 @rowsAffected 			-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 				-- INOUT p_commission_payout_detail_id INT(10)
				,@commissionPayoutId 	-- IN p_commission_payout_id INT(11)
				,@orderId 				-- IN p_order_id INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@level 				-- IN p_level INT(11)
				,@value 				-- IN p_value VARCHAR(1024)
				,@percent 				-- IN p_percent DECIMAL(4,1)
				,@amount 				-- IN p_amount VARCHAR(1024)
				,@commissionOrderId 	-- IN p_commission_order_id INT(11)
			);
		END LOOP loop_i;
		CLOSE fetchOrders;
	ELSEIF (p_commissionType = RETAIL) THEN
		--
		-- Retail commission.
		--
		OPEN fetchOrders;
		loop_i: LOOP
			FETCH fetchOrders INTO
				 v_ithId
				,v_ithSellerId
				,v_ithPassupSponsorId
				,v_ithSoldToId
				,v_ithShoppingCartId
				,v_ithSalesCount
				,v_ithCommissionType
				,v_ithCommissionPercentage
				,v_ithNotIncluded
				,v_ithCv
				,v_ithCmv
				,v_ithCd;
			IF v_done THEN
				SET v_done = FALSE;
				LEAVE loop_i;
			END IF;
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @p_commission_payout_id = 0; -- Primary key generated inside.
			SET @commissionType = RETAIL;
			SET @userId = v_ithSellerId;
			SET @commissionPeriodId = p_commission_period_id;
			SET @level = 0;
			SET @value = FORMAT(v_ithCv * .50, 2);
			SET @isPaid = 0;
			CALL sp_save_cm_commission_payouts(
				 @rowsAffected 				-- OUT p_rows_affected INT(11)
				,@operations 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_commission_payout_id 	-- INOUT p_commission_payout_id INT(10)
				,@commissionType 			-- IN p_commission_payout_type_id INT(11)
				,@userId 					-- IN p_user_id INT(11)
				,@commissionPeriodId 		-- IN p_commission_period_id INT(11)
				,@level 					-- IN p_level INT(11)
				,@value 					-- IN p_value VARCHAR(1048)
				,@isPaid 					-- IN p_is_paid INT(11)
			);
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @newKey = 0;
			SET @commissionPayoutId = @p_commission_payout_id;
			SET @orderId = v_ithShoppingCartId;
			SET @userId = v_ithSoldToId; -- Buyer.
			SET @level = 0;
			SET @value = FORMAT(v_ithCv * .50, 2);
			SET @percent = 1;
			SET @amount = FORMAT(v_ithCv * .50, 2);
			SET @commissionOrderId = v_ithId;
			CALL sp_save_cm_commission_payouts_details(
				 @rowsAffected 			-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 				-- INOUT p_commission_payout_detail_id INT(10)
				,@commissionPayoutId 	-- IN p_commission_payout_id INT(11)
				,@orderId 				-- IN p_order_id INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@level 				-- IN p_level INT(11)
				,@value 				-- IN p_value VARCHAR(1024)
				,@percent 				-- IN p_percent DECIMAL(4,1)
				,@amount 				-- IN p_amount VARCHAR(1024)
				,@commissionOrderId 	-- IN p_commission_order_id INT(11)
			);
		END LOOP loop_i;
		CLOSE fetchOrders;
	ELSEIF (p_commissionType = CODED_COMM) THEN
		--
		-- Coded commission.
		--
		OPEN fetchOrders;
		loop_i: LOOP
			FETCH fetchOrders INTO
				 v_ithId
				,v_ithSellerId
				,v_ithPassupSponsorId
				,v_ithSoldToId
				,v_ithShoppingCartId
				,v_ithSalesCount
				,v_ithCommissionType
				,v_ithCommissionPercentage
				,v_ithNotIncluded
				,v_ithCv
				,v_ithCmv
				,v_ithCd;
			IF v_done THEN
				SET v_done = FALSE;
				LEAVE loop_i;
			END IF;
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @p_commission_payout_id = 0;
			SET @commissionType = CODED_COMM;
			SET @userId = v_ithSellerId;
			SET @commissionPeriodId = p_commission_period_id;
			SET @level = 0;
			SET @value = v_ithCd;
			SET @isPaid = 0;
			CALL sp_save_cm_commission_payouts(
				 @rowsAffected 				-- OUT p_rows_affected INT(11)
				,@operations 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_commission_payout_id 	-- INOUT p_commission_payout_id INT(10)
				,@commissionType 			-- IN p_commission_payout_type_id INT(11)
				,@userId 					-- IN p_user_id INT(11)
				,@commissionPeriodId 		-- IN p_commission_period_id INT(11)
				,@level 					-- IN p_level INT(11)
				,@value 					-- IN p_value VARCHAR(1048)
				,@isPaid 					-- IN p_is_paid INT(11)
			);			
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @newKey = 0;
			SET @commissionPayoutId = @p_commission_payout_id;
			SET @orderId = v_ithShoppingCartId;
			SET @userId = v_ithSoldToId;
			SET @level = 0;
			SET @value = v_ithCd;
			SET @percent = 1;
			SET @amount = v_ithCd;
			SET @commissionOrderId = v_ithId;
			CALL sp_save_cm_commission_payouts_details(
				 @rowsAffected 			-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 				-- INOUT p_commission_payout_detail_id INT(10)
				,@commissionPayoutId 	-- IN p_commission_payout_id INT(11)
				,@orderId 				-- IN p_order_id INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@level 				-- IN p_level INT(11)
				,@value 				-- IN p_value VARCHAR(1024)
				,@percent 				-- IN p_percent DECIMAL(4,1)
				,@amount 				-- IN p_amount VARCHAR(1024)
				,@commissionOrderId 	-- IN p_commission_order_id INT(11)
			);
		END LOOP loop_i;
		CLOSE fetchOrders;
	ELSEIF (p_commissionType = CHECKMATCH_COMM) THEN
		--
		-- Checkmatch commission.
		--
		OPEN fetchOrders;
		loop_i: LOOP
			FETCH fetchOrders INTO
				 v_ithId
				,v_ithSellerId
				,v_ithPassupSponsorId
				,v_ithSoldToId
				,v_ithShoppingCartId
				,v_ithSalesCount
				,v_ithCommissionType
				,v_ithCommissionPercentage
				,v_ithNotIncluded
				,v_ithCv
				,v_ithCmv
				,v_ithCd;
			IF v_done THEN
				SET v_done = FALSE;
				LEAVE loop_i;
			END IF;
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @p_commission_payout_id = 0;
			SET @commissionType = CHECKMATCH_COMM;
			SET @userId = v_ithSellerId;
			SET @commissionPeriodId = p_commission_period_id;
			SET @level = 0;
			SET @value = v_ithCmv;
			SET @isPaid = 0;
			CALL sp_save_cm_commission_payouts(
				 @rowsAffected 				-- OUT p_rows_affected INT(11)
				,@operations 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_commission_payout_id 	-- INOUT p_commission_payout_id INT(10)
				,@commissionType 			-- IN p_commission_payout_type_id INT(11)
				,@userId 					-- IN p_user_id INT(11)
				,@commissionPeriodId 		-- IN p_commission_period_id INT(11)
				,@level 					-- IN p_level INT(11)
				,@value 					-- IN p_value VARCHAR(1048)
				,@isPaid 					-- IN p_is_paid INT(11)
			);
			
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @newKey = 0;
			SET @commissionPayoutId = @p_commission_payout_id;
			SET @orderId = v_ithShoppingCartId;
			SET @userId = v_ithSoldToId;
			SET @level = 0;
			SET @value = v_ithCmv;
			SET @percent = 1;
			SET @amount = v_ithCmv;
			SET @commissionOrderId = v_ithId;
			CALL sp_save_cm_commission_payouts_details(
				 @rowsAffected 			-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 				-- INOUT p_commission_payout_detail_id INT(10)
				,@commissionPayoutId 	-- IN p_commission_payout_id INT(11)
				,@orderId 				-- IN p_order_id INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@level 				-- IN p_level INT(11)
				,@value 				-- IN p_value VARCHAR(1024)
				,@percent 				-- IN p_percent DECIMAL(4,1)
				,@amount 				-- IN p_amount VARCHAR(1024)
				,@commissionOrderId 	-- IN p_commission_order_id INT(11)
			);
		END LOOP loop_i;
		CLOSE fetchOrders;
	END IF;
	
	END PROC;
	
END //
DELIMITER ;