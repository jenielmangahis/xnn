DROP FUNCTION IF EXISTS sp_getTotalCIPerAffiliate;
DELIMITER //
CREATE FUNCTION sp_getTotalCIPerAffiliate(
	 p_date_from VARCHAR(30)
	,p_date_to VARCHAR(30)
	,p_user_id INT(11)
) RETURNS int(11)
BEGIN
	/*
	Definition of Terms:
		1. 1001 - Affiliate category id.
		2. 1 is_processed - denotes a transaction record that it is finish processed in passup.
	Pre-condition:
		Requires date range(from and to) and id of the user.
	Post-condition:
		Return the total CI point of a user(p_user_id) on free member sales. */
	RETURN
		COALESCE(
		   (SELECT
				SUM(scp.ci) AS total_ci
			FROM transactions AS trn
			INNER JOIN shoppingcart_products AS scp ON (scp.id = trn.itemid)
			INNER JOIN users ON (trn.sponsorid = users.id)
			WHERE (trn.is_processed = 1) AND
				  ((trn.transactiondate >= p_date_from) AND (trn.transactiondate <= p_date_to)) AND
				  (users.id = p_user_id) AND
				  (trn.userid NOT IN (
						SELECT users.id FROM users
						INNER JOIN (
							SELECT categorymap.userid FROM categorymap
							WHERE (categorymap.catid = 1001)
						) AS sub ON (sub.userid = users.id)
				  ))
			ORDER BY trn.id ASC)
			, 0
		);
		
END //
DELIMITER ;