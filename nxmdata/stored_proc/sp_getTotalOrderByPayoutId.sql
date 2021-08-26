DROP FUNCTION IF EXISTS sp_getTotalOrderByPayoutId;
DELIMITER //
CREATE FUNCTION sp_getTotalOrderByPayoutId(
	p_payout_id INT(11)
) RETURNS INT(11)
BEGIN
	RETURN
		COALESCE(
			(
				SELECT 
					COUNT(*) AS cnt
				FROM cm_commission_payout_details AS cpd
				WHERE (cpd.commission_payout_id = p_payout_id)
			)
			,0.00
		);
		
END //
DELIMITER ;