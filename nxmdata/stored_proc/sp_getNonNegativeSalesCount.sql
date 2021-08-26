DROP FUNCTION IF EXISTS sp_getNonNegativeSalesCount;
DELIMITER //
CREATE FUNCTION sp_getNonNegativeSalesCount(
	 p_user_id INT(11)
	,p_product_id INT(11)
) RETURNS INT(11)
BEGIN
	DECLARE v_idx INT(11) DEFAULT 0;
	DECLARE v_len INT(11) DEFAULT 0;
	DECLARE v_ith_user_id INT(11) DEFAULT 0;
	DECLARE v_ith_sales_count INT(11) DEFAULT 0;
	
	SELECT cn.`path` FROM cm_nodes AS cn
	WHERE (cn.member_id = p_user_id)
	INTO @node_path;	
	
	BLK: BEGIN
	
	SET v_ith_user_id = p_user_id;
	SET v_len = (LENGTH(@node_path) - LENGTH(REPLACE(@node_path, ',', ''))) + 1;	
	SET v_idx = 0;	
	WHILE (v_ith_user_id > 0) DO
		
		SET v_ith_sales_count = sp_getSalesCount(v_ith_user_id, p_product_id);
		IF (v_ith_sales_count >= 0) OR (v_ith_user_id = sp_GLOBAL('ADMIN')) THEN
			LEAVE BLK;
		END IF;
		
		SET v_idx = v_idx + 1;
		SET v_ith_user_id = sp_get_element_at(@node_path, v_idx);
	END WHILE;
	
	END BLK;
	
	RETURN v_ith_user_id;
END //
DELIMITER ;