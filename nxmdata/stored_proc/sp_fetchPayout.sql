DROP PROCEDURE IF EXISTS sp_fetchPayout;
DELIMITER //
CREATE PROCEDURE sp_fetchPayout(
	 IN p_key ENUM('DEFAULT', 'DETAILS')
	,IN p_commission_period_id INT(11)
)
BEGIN
	-- Constant commission type.
	DECLARE RETAIL INT(11) DEFAULT 			1; -- Retail Commissions
	DECLARE RETAIL_POOL INT(11) DEFAULT 	2; -- Retail Pool
	DECLARE CODED_COMM INT(11) DEFAULT 		3; -- Coded Commissions
	DECLARE CHECKMATCH_COMM INT(11) DEFAULT 4; -- Check Match Commissions
	DECLARE BARRY_COMM INT(11) DEFAULT 		5; -- Barry's Commissions

	SELECT 
		cp.commission_period_type_id
	FROM cm_commission_periods AS cp WHERE (cp.commission_period_id = p_commission_period_id)
	INTO @commissionType;

	IF (p_key = 'DEFAULT') THEN

		IF (@commissionType = RETAIL) THEN

			SELECT
				 users.lname 				AS first_name
				,users.fname 				AS last_name
				,users.business 			AS bussiness_name
				,users.site 				AS user_name
				,users.id 					AS member_id
				,ROUND(SUM(cp.value), 2) 	AS total_payout
				,sp_getTotalRetailCommPerAffiliate(
					 cpd.start_date
					,cpd.end_date
					,users.id
				 ) 							AS total_retail
				,cp.level 					AS total_shares
				,sp_getTotalOrderByPayoutId(
					cp.commission_payout_id
				) 							AS total_orders
			FROM users
			LEFT JOIN cm_commission_payouts 	AS cp ON (users.id = cp.user_id)
			INNER JOIN cm_commission_periods 	AS cpd ON (cpd.commission_period_id = cp.commission_period_id)
			WHERE (cp.commission_period_id = p_commission_period_id)
			GROUP BY cp.user_id
			ORDER BY total_payout DESC;	

		ELSEIF (@commissionType = RETAIL_POOL) THEN
			
			SELECT
				 users.lname 				AS first_name
				,users.fname 				AS last_name
				,users.business 			AS bussiness_name
				,users.site 				AS user_name
				,users.id 					AS member_id
				,ROUND(SUM(cp.value), 2) 	AS total_payout
				,sp_getTotalCIPerAffiliate(
					 cpd.start_date
					,cpd.end_date
					,users.id
				 ) 							AS total_ci
				,cp.level 					AS total_shares
				,sp_getTotalOrderByPayoutId(
					cp.commission_payout_id
				) 							AS total_orders
			FROM users
			LEFT JOIN cm_commission_payouts 	AS cp ON (users.id = cp.user_id)
			INNER JOIN cm_commission_periods 	AS cpd ON (cpd.commission_period_id = cp.commission_period_id)
			WHERE (cp.commission_period_id = p_commission_period_id)
			GROUP BY cp.user_id
			ORDER BY total_payout DESC;

		ELSEIF (@commissionType = CHECKMATCH_COMM) THEN

			SELECT
				 users.lname 				AS first_name
				,users.fname 				AS last_name
				,users.business 			AS bussiness_name
				,users.site 				AS user_name
				,users.id 					AS member_id
				,ROUND(SUM(cp.value), 2) 	AS total_payout
				,sp_getTotalCheckmatchCommPerAffiliate(
					 users.id
					,cpd.start_date
					,cpd.end_date
				 ) 							AS total_checkmatch
				,cp.level 					AS total_shares
				,sp_getTotalOrderByPayoutId(
					cp.commission_payout_id
				) 							AS total_orders
			FROM users
			LEFT JOIN cm_commission_payouts 	AS cp ON (users.id = cp.user_id)
			INNER JOIN cm_commission_periods 	AS cpd ON (cpd.commission_period_id = cp.commission_period_id)
			WHERE (cp.commission_period_id = p_commission_period_id)
			GROUP BY cp.user_id
			ORDER BY total_payout DESC;
		ELSEIF (@commissionType = CODED_COMM) THEN

			SELECT
				 users.lname 				AS first_name
				,users.fname 				AS last_name
				,users.business 			AS bussiness_name
				,users.site 				AS user_name
				,users.id 					AS member_id
				,ROUND(SUM(cp.value), 2) 	AS total_payout
				,sp_getTotalCodedCommPerAffiliate(
					 users.id
					,cpd.start_date
					,cpd.end_date
				 ) 							AS total_checkmatch
				,cp.level 					AS total_shares
				,sp_getTotalOrderByPayoutId(
					cp.commission_payout_id
				) 							AS total_orders
			FROM users
			LEFT JOIN cm_commission_payouts 	AS cp ON (users.id = cp.user_id)
			INNER JOIN cm_commission_periods 	AS cpd ON (cpd.commission_period_id = cp.commission_period_id)
			WHERE (cp.commission_period_id = p_commission_period_id)
			GROUP BY cp.user_id
			ORDER BY total_payout DESC;
		ELSEIF (@commissionType = BARRY_COMM) THEN

			SELECT
				 users.lname 				AS first_name
				,users.fname 				AS last_name
				,users.business 			AS bussiness_name
				,users.site 				AS user_name
				,users.id 					AS member_id
				,ROUND(SUM(cp.value), 2) 	AS total_payout
				,sp_getTotalBarryCommPerAffiliate(
					 users.id
					,cpd.start_date
					,cpd.end_date
				 ) 							AS total_checkmatch
				,cp.level 					AS total_shares
				,sp_getTotalOrderByPayoutId(
					cp.commission_payout_id
				) 							AS total_orders
			FROM users
			LEFT JOIN cm_commission_payouts 	AS cp ON (users.id = cp.user_id)
			INNER JOIN cm_commission_periods 	AS cpd ON (cpd.commission_period_id = cp.commission_period_id)
			WHERE (cp.commission_period_id = p_commission_period_id)
			GROUP BY cp.user_id
			ORDER BY total_payout DESC;			
		END IF;

	ELSEIF (p_key = 'DETAILS') THEN

		IF (@commissionType = BARRY_COMM) THEN

			SELECT
				 transactions.id 							AS `Order Number`
				,CONCAT(seller.fname, ' ', seller.lname) 	AS `Name`
				,seller.id									AS `ID`
				,2.00										AS `Volume`
			FROM cm_commission_payout_details
			INNER JOIN cm_commission_payouts 		ON (cm_commission_payouts.commission_payout_id = cm_commission_payout_details.commission_payout_id)
			INNER JOIN cm_commission_orders 		ON (cm_commission_orders.id = cm_commission_payout_details.commission_order_id)
			INNER JOIN users AS seller 				ON (seller.id = cm_commission_payout_details.user_id)
			INNER JOIN users AS buyer 				ON (buyer.id = cm_commission_orders.sold_to_id)
			INNER JOIN cm_commission_payout_types 	ON (cm_commission_payout_types.commission_payout_type_id = cm_commission_payouts.commission_payout_type_id)
			INNER JOIN transactions 				ON (transactions.id = cm_commission_orders.shopping_cart_id)
			WHERE (cm_commission_payouts.commission_period_id = p_commission_period_id)
			ORDER BY transactions.id ASC;
		ELSE

			SELECT
				 CONCAT('(', seller.id, ')-', seller.fname, ' ', seller.lname) 	AS seller
				,CONCAT('(', buyer.id, ')-', buyer.fname, ' ', buyer.lname) 	AS sold_to
				,cm_commission_payouts.`value`									AS payout_detail
				,cm_commission_payout_types.description							AS payout_type
				,cm_commission_orders.shopping_cart_id							AS order_id
				,transactions.itemid											AS sku
				,cm_commission_payout_details.`value`							AS volume_calculated_from
				,cm_commission_payout_details.percent							AS percent_payout
				,cm_commission_orders.sales_count								AS sales_count
			FROM cm_commission_payout_details
			INNER JOIN cm_commission_payouts 		ON (cm_commission_payouts.commission_payout_id = cm_commission_payout_details.commission_payout_id)
			INNER JOIN cm_commission_orders 		ON (cm_commission_orders.id = cm_commission_payout_details.commission_order_id)
			INNER JOIN users AS seller 				ON (seller.id = cm_commission_orders.seller_id)
			INNER JOIN users AS buyer 				ON (buyer.id = cm_commission_orders.sold_to_id)
			INNER JOIN cm_commission_payout_types 	ON (cm_commission_payout_types.commission_payout_type_id = cm_commission_payouts.commission_payout_type_id)
			INNER JOIN transactions 				ON (transactions.id = cm_commission_orders.shopping_cart_id)
			WHERE (cm_commission_payouts.commission_period_id = p_commission_period_id);
		END IF;
		
	END IF;
	
END //
DELIMITER ;