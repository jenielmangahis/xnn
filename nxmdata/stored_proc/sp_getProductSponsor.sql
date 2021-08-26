DROP FUNCTION IF EXISTS sp_getProductSponsor;
DELIMITER //
CREATE FUNCTION sp_getProductSponsor(
	 p_user_id INT(11)
	,p_product_id INT(11)
) RETURNS INT(11)
BEGIN
	RETURN
		COALESCE(
			(
			 SELECT 
				ps.product_sponsor_id
			 FROM cm_product_sponsors AS ps
			 WHERE (ps.user_id = p_user_id) AND 
				   (ps.product_id = p_product_id)
			 LIMIT 1
			)
			,-1
		);
END //
DELIMITER ;