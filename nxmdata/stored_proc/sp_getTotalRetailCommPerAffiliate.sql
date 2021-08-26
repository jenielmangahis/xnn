DROP FUNCTION IF EXISTS sp_getTotalRetailCommPerAffiliate;
DELIMITER //
CREATE FUNCTION sp_getTotalRetailCommPerAffiliate(
	 p_user_id INT(11)
	,p_date_from VARCHAR(30)
	,p_date_to VARCHAR(30)
) RETURNS DECIMAL(11, 2)
BEGIN
	/*
	Definition of Terms:
		Retail Commissions: 
			This a commission earned by the affiliate when they sell a product directly to a
			Free Member. The affiliate gets a 50% commission of the CV of the SKUs they sell each month. This
			commission type is paid daily.
		1. 1001 - Affiliate category id.
		2. 1 is_processed - denotes a transaction record that it is finish processed in passup.
	Pre-condition:
		Requires date range(from and to) and id of the user.
	Post-condition:
		Return the total retail commision of a user(p_user_id) on free member sales. */
	DECLARE RETAIL INT(11) DEFAULT 				1; -- Retail Commissions
	DECLARE RETAIL_POOL INT(11) DEFAULT 		2; -- Retail Pool
	DECLARE CODED_COMM INT(11) DEFAULT 			3; -- Coded Commissions
	DECLARE CHECKMATCH_COMM INT(11) DEFAULT 	4; -- Check Match Commissions
	DECLARE BARRY_COMM INT(11) DEFAULT 			5; -- Barry's Commissions
	
	RETURN
		COALESCE(
			(
				SELECT
					SUM(tblRetailCommission.`value`) AS `value`
				FROM (
					SELECT
						 DATE_FORMAT(trn.transactiondate, '%Y-%m-%d') 			AS trans_date
						,shoppingcart_products.cv * 0.5							AS `value`
					FROM cm_commission_orders AS co
					INNER JOIN transactions AS trn 		ON (trn.id = co.shopping_cart_id)
					INNER JOIN shoppingcart_products 	ON (shoppingcart_products.id = trn.itemid)
					WHERE
						(co.commission_type = RETAIL) AND
						(sp_isFreeMember(co.sold_to_id) = 'TRUE')
					GROUP BY co.id
				) AS tblRetailCommission
				WHERE
					(
						(tblRetailCommission.trans_date >= p_date_from) AND
						(tblRetailCommission.trans_date <= p_date_to)
					) AND
					(tblRetailCommission.seller_id = p_user_id)
			)
			,0.00
		);
		
END //
DELIMITER ;