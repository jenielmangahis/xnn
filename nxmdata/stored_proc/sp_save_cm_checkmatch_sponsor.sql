DROP PROCEDURE IF EXISTS sp_save_cm_checkmatch_sponsor;
DELIMITER //
CREATE PROCEDURE sp_save_cm_checkmatch_sponsor(
	 IN 	p_user_login INT(11)
	,OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_id INT(11)
	,IN     p_user_id INT(11)
	,IN     p_product_id INT(11)
	,IN     p_checkmatch_sponsor_id INT(11)
	,IN     p_order_id INT(11)
)
BEGIN
	
	PROC: BEGIN
	
	IF (sp_is_column_exists('cm_checkmatch_sponsor', 'order_id') = 'FALSE') THEN
	
		ALTER TABLE cm_checkmatch_sponsor
		ADD COLUMN order_id INT(11) DEFAULT 0 AFTER checkmatch_sponsor_id;
	END IF;
	
	IF (p_operation = 'ADD') THEN
		
		INSERT INTO cm_checkmatch_sponsor(
			 user_id
			,product_id
			,checkmatch_sponsor_id
			,order_id
			,created_at)
		VALUES(
			 p_user_id
			,p_product_id
			,p_checkmatch_sponsor_id
			,p_order_id
			,DATE_FORMAT(NOW(), '%Y-%m-%d %h:%i %p'));
		SET p_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	ELSEIF (p_operation = 'EDIT') THEN
		
		UPDATE cm_checkmatch_sponsor
		SET  user_id = p_user_id
			,product_id = p_product_id
			,checkmatch_sponsor_id = p_checkmatch_sponsor_id
			,order_id = p_order_id
			,updated_at = DATE_FORMAT(NOW(), '%Y-%m-%d %h:%i %p')
		WHERE (id = p_id);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
		
	END IF;
	
	END PROC;
END