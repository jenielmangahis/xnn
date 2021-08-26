DROP FUNCTION IF EXISTS sp_getTotalCheckmatchCommPerAffiliate;
DELIMITER //
CREATE FUNCTION sp_getTotalCheckmatchCommPerAffiliate(
	 p_user_id INT(11)
	,p_date_from VARCHAR(30)
	,p_date_to VARCHAR(30)
) RETURNS DECIMAL(14, 2) /* 000,000,000.00 */
BEGIN
	DECLARE RETAIL INT(11) DEFAULT 				1; -- Retail Commissions
	DECLARE RETAIL_POOL INT(11) DEFAULT 		2; -- Retail Pool
	DECLARE CODED_COMM INT(11) DEFAULT 			3; -- Coded Commissions
	DECLARE CHECKMATCH_COMM INT(11) DEFAULT 	4; -- Check Match Commissions
	DECLARE BARRY_COMM INT(11) DEFAULT 			5; -- Barry's Commissions
	
	RETURN
		COALESCE(
			(
				SELECT 
					SUM(tblCheckmatchCommission.cmv) AS total
				FROM (
					SELECT
						 DATE_FORMAT(trn.transactiondate, '%Y-%m-%d') AS trans_date
						,co.seller_id AS seller_id
						,co.sold_to_id AS buyer_id
						,affilliateUsers.catid AS category_id
						,co.shopping_cart_id AS trans_id
						,shoppingcart_products.sku AS sku
						,shoppingcart_products.cmv AS cmv
					FROM cm_commission_orders AS co
					INNER JOIN transactions AS trn ON (trn.id = co.shopping_cart_id)
					INNER JOIN shoppingcart_products ON (shoppingcart_products.id = trn.itemid)
					INNER JOIN (
						SELECT 
							 users.id AS id
							,categorymap.catid AS catid
						FROM users
						INNER JOIN categorymap ON (categorymap.userid = users.id)
						WHERE (categorymap.catid = 1001) AND 
							  (users.active = 'Yes') AND 
							  (users.id = p_user_id)
						GROUP BY categorymap.userid, categorymap.catid
					) AS affilliateUsers ON (affilliateUsers.id = co.seller_id)
					WHERE (co.commission_type = CHECKMATCH_COMM)
				) AS tblCheckmatchCommission
				WHERE
					(
						(DATE_FORMAT(tblCheckmatchCommission.trans_date, '%Y-%m-%d') >= p_date_from) AND
						(DATE_FORMAT(tblCheckmatchCommission.trans_date, '%Y-%m-%d') <= p_date_to)
					) AND
					(tblCheckmatchCommission.seller_id = p_user_id)
			)
			,0.00
		);
		
END //
DELIMITER ;