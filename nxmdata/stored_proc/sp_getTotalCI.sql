DROP FUNCTION IF EXISTS sp_getTotalCI;
DELIMITER //
CREATE FUNCTION sp_getTotalCI(
	 p_date_from VARCHAR(30)
	,p_date_to VARCHAR(30)
) RETURNS INT(11)
BEGIN
	/*
	Definition of Terms:
		1. 1000 - category id of customer or free member. 
		2. is_processed = 1 - denotes that the transaction is finish in passup process done by cron
	Pre-condition:
		1. p_date_from - the starting date in which transactions are evaluated.
		2. p_date_to - the ending date in which the transactions are evaluated. 
		3. Users with category id 1000 must be present on the database.
		4. Transaction table must have a data.
	Post-condition:
		Return the sum of ci of shoppingcart_products that are present on transaction table
		within the given date range. 
		   This is useful on calculating retail pool commission. */
	RETURN
		COALESCE(
			(
			 SELECT
				SUM(scp.ci) AS total_ci
			 FROM transactions AS trn
			 INNER JOIN shoppingcart_products AS scp ON (scp.id = trn.itemid)
			 INNER JOIN users ON (trn.userid = users.id)
			 INNER JOIN (
				SELECT
					 categorymap.catid
					,categorymap.userid
				FROM categorymap
				WHERE (categorymap.catid = 1000)
			 ) AS ctm ON (ctm.userid = users.id)
			 WHERE (trn.is_processed = 1) AND
				   ((trn.transactiondate >= p_date_from) AND (trn.transactiondate <= p_date_to)) AND
			 	   (ctm.catid = 1000)
			)
			,0
		);
		
END //
DELIMITER ;