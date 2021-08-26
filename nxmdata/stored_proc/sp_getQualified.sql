DROP FUNCTION IF EXISTS sp_getQualified;
DELIMITER //
CREATE FUNCTION sp_getQualified(
	 p_user_id INT(11)
	,p_product_id INT(11)
	,p_phase ENUM('PHASE_1', 'PHASE_2')
) RETURNS INT(11)
BEGIN
	DECLARE v_idx INT(11) DEFAULT 0;
	DECLARE v_len INT(11) DEFAULT 0;
	DECLARE v_ith_user_id INT(11) DEFAULT 0;
	DECLARE v_ith_sales_count INT(11) DEFAULT 0;
	DECLARE v_qualified_id INT(11) DEFAULT 0;
	
	SELECT cn.`path` FROM cm_nodes AS cn 
	WHERE (cn.member_id = p_user_id)
	LIMIT 1
	INTO @node_path;
	
	BLK: BEGIN
	
	SET v_ith_user_id = p_user_id;
	SET v_qualified_id = -1;
	SET v_len = (LENGTH(@node_path) - LENGTH(REPLACE(@node_path, ',', ''))) + 1;	
	SET v_idx = 0;
	WHILE (v_idx < v_len) DO
		
		IF (p_phase = 'PHASE_1') THEN
			
			SET v_ith_sales_count = sp_getSalesCount(v_ith_user_id, p_product_id) + 1;
			IF (sp_get_passup_phase(v_ith_sales_count) = 'NONE') OR 
			   (v_ith_user_id = sp_GLOBAL('ADMIN')) THEN
			   
				SET v_qualified_id = v_ith_user_id;
				LEAVE BLK;
			END IF;
		ELSEIF (p_phase = 'PHASE_2') THEN
			
			SET v_ith_sales_count = sp_getSalesCount(v_ith_user_id, p_product_id) + 1;
			IF (v_ith_sales_count >= 12) THEN
			
				SET v_qualified_id = v_ith_user_id;
				LEAVE BLK;
			ELSE
			
				SET v_qualified_id = -1;
				LEAVE BLK;
			END IF;
		END IF;
		
		SET v_idx = v_idx + 1;
		SET v_ith_user_id = sp_getElementAt(@node_path, v_idx);		
	END WHILE;
		
	END BLK;
	
	RETURN v_qualified_id;
END //
DELIMITER ;