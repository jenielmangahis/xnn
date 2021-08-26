DROP FUNCTION IF EXISTS sp_getCheckmatchSponsor;
DELIMITER //
CREATE FUNCTION sp_getCheckmatchSponsor(
	 p_user_id INT(11)
	,p_product_id INT(11)
) RETURNS INT(11)
BEGIN
	RETURN
		COALESCE(
			(
			 SELECT 
				ps.checkmatch_sponsor_id
			 FROM cm_checkmatch_sponsor AS ps
			 WHERE (ps.user_id = p_user_id) AND 
				   (ps.product_id = p_product_id)
			 LIMIT 1
			)
			,-1
		);
END //
DELIMITER ;