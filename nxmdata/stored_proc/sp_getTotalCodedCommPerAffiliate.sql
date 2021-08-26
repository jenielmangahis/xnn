DROP FUNCTION IF EXISTS sp_getTotalCodedCommPerAffiliate;
DELIMITER //
CREATE FUNCTION sp_getTotalCodedCommPerAffiliate(
	 p_user_id INT(11)
	,p_date_from VARCHAR(30)
	,p_date_to VARCHAR(30)
) RETURNS DECIMAL(14, 2)
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
					SUM(tblRetailCommission.cd) AS total
				FROM (
					SELECT
						 DATE_FORMAT(trn.transactiondate, '%Y-%m-%d') AS trans_date
						,co.seller_id AS seller_id
						,co.sold_to_id AS buyer_id
						,affiliateMemberUsers.catid AS category_id
						,co.shopping_cart_id AS trans_id
						,shoppingcart_products.sku AS sku
						,shoppingcart_products.cd AS cd
					FROM cm_commission_orders AS co
					INNER JOIN transactions AS trn ON (trn.id = co.shopping_cart_id)
					INNER JOIN shoppingcart_products ON (shoppingcart_products.id = trn.itemid)
					INNER JOIN (
						-- List of free member users.
						SELECT 
							 users.id
							,categorymap.catid
						FROM users
						INNER JOIN categorymap ON (categorymap.userid = users.id)
						WHERE (categorymap.catid = 1001) AND 
							  (users.active = 'Yes')
						GROUP BY categorymap.userid, categorymap.catid
					) AS affiliateMemberUsers ON (affiliateMemberUsers.id = co.sold_to_id)
					WHERE (co.commission_type = CODED_COMM)
				) AS tblRetailCommission
				WHERE
					(
						(DATE_FORMAT(tblRetailCommission.trans_date, '%Y-%m-%d') >= p_date_from) AND
						(DATE_FORMAT(tblRetailCommission.trans_date, '%Y-%m-%d') <= p_date_to)
					) AND
					(tblRetailCommission.seller_id = p_user_id)
			)
			,0.00
		);
		
END //
DELIMITER ;