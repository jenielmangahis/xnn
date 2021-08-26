DROP PROCEDURE IF EXISTS sp_save_cm_checkmatch;
DELIMITER //
CREATE PROCEDURE sp_save_cm_checkmatch(
	 IN 	p_user_login INT(11)
	,OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_id INT(11)
	,IN 	p_user_id INT(11)
	,IN     p_product_id INT(11)
	,IN     p_is_qualified ENUM('No','Yes')
)
BEGIN
	
	IF (sp_is_table_exists('cm_checkmatch') = 'FALSE') THEN
		
		CREATE TABLE `cm_checkmatch` (
			 `id` int(11) NOT NULL AUTO_INCREMENT
			,`user_id` int(11) DEFAULT NULL
			,`product_id` int(11) DEFAULT NULL
			,`is_qualified` enum('No','Yes') DEFAULT NULL
			,PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=latin1;
	END IF;
	
	PROC: BEGIN
		
	IF (p_operation = 'ADD') THEN
		
		INSERT INTO cm_checkmatch(
			 user_id
			,product_id
			,is_qualified)
		VALUES(
			 p_user_id
			,p_product_id
			,p_is_qualified);
		SET p_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	END IF;
	
	END PROC;
END //
DELIMITER ;