DROP FUNCTION IF EXISTS sp_getTotalPayoutByDatePerAffiliate;
DELIMITER //
CREATE FUNCTION sp_getTotalPayoutByDatePerAffiliate(
	 p_user_id INT(11)
	,p_date VARCHAR(10)
) RETURNS DECIMAL(14, 2)
BEGIN
	RETURN
		COALESCE(
			(
				SELECT 
					SUM(cp.`value`) AS total
				FROM cm_commission_payouts AS cp
				WHERE 
					(cp.datestamp = p_date) AND 
					(cp.user_id = p_user_id)
				GROUP BY cp.user_id
			)
			,0.00
		);

END //
DELIMITER ;