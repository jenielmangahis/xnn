DROP FUNCTION IF EXISTS sp_getSalesCountPerMonth;
DELIMITER //
CREATE FUNCTION sp_getSalesCountPerMonth(
	 p_user_id INT(11)	-- The user whom sales count is evaluated.
	,p_sku INT(11) 		-- sku to be counted.
	,p_from VARCHAR(10) -- The date from 2016-01-01 date format.
	,p_to VARCHAR(10) 	-- The date to 2016-01-01 date format.
) RETURNS INT(11)
BEGIN
	/*
	Get the sales of the affiliate from Retails and Passup. 
	Within the specific date range. */
	RETURN
		COALESCE(
			(
			 SELECT
				COUNT(*)
			 FROM transactions AS trn
			 WHERE
				(trn.sponsorid = p_user_id) AND
				(trn.is_processed = 1) AND
				(trn.`type` = 'product') AND
				(trn.itemid = p_sku) AND
				(trn.transactiondate BETWEEN p_from AND p_to)
			 GROUP BY trn.sponsorid
			)
			,0
		);
END //
DELIMITER ;