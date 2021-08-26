DROP PROCEDURE IF EXISTS sp_save_cm_product_sponsors;
DELIMITER //
CREATE PROCEDURE sp_save_cm_product_sponsors(
	 IN 	p_user_login INT(11)
	,OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_id INT(11)
	,IN     p_user_id INT(11)
	,IN     p_product_id INT(11)
	,IN     p_product_sponsor_id INT(11)
	,IN 	p_order_id INT(11)
)
BEGIN
	
	PROC: BEGIN
	
	IF (sp_is_column_exists('cm_product_sponsors', 'order_id') = 'FALSE') THEN
		ALTER TABLE `cm_product_sponsors`
		ADD COLUMN `order_id` INT(11) NULL AFTER `product_sponsor_id`;
	END IF;
	
	IF (p_operation = 'ADD') THEN
		
		INSERT INTO cm_product_sponsors(
			 user_id
			,product_id
			,product_sponsor_id
			,order_id
			,created_at)
		VALUES(
			 p_user_id
			,p_product_id
			,p_product_sponsor_id
			,p_order_id
			,DATE_FORMAT(NOW(), '%Y-%m-%d %h:%i %p'));
		SET p_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);

	ELSEIF (p_operation = 'EDIT') THEN
		
		UPDATE cm_product_sponsors
		SET  user_id = p_user_id
			,product_id = p_product_id
			,product_sponsor_id = p_product_sponsor_id
			,order_id = p_order_id
			,updated_at = DATE_FORMAT(NOW(), '%Y-%m-%d %h:%i %p')
		WHERE (id = p_id);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
		
	END IF;
	
	END PROC;
END