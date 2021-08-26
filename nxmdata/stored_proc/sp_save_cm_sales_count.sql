DROP PROCEDURE IF EXISTS `sp_save_cm_sales_count`;
DELIMITER //
CREATE PROCEDURE `sp_save_cm_sales_count`(
	 IN  p_user_login INT(11)
	,OUT p_rows_affected INT(11)
	,IN  p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,IN  p_user_id INT(11)
	,IN	 p_product_id INT(11)
	,IN  p_sales_count INT(11)
	,IN  p_sales_count_since VARCHAR(30)
	,IN  p_sales_count_until VARCHAR(30)
)
BEGIN
	
	IF (sp_is_table_exists('cm_sales_count') = 'FALSE') THEN
		CREATE TABLE cm_sales_count (
			 user_id INT(11) NOT NULL
			,product_id INT(11) NOT NULL
			,sales_count INT(11) DEFAULT 0
			,sales_count_since VARCHAR(30) DEFAULT ''
			,sales_count_until VARCHAR(30) DEFAULT ''
			,PRIMARY KEY (product_id, user_id)
		) ENGINE = InnoDB DEFAULT CHARSET=latin1;		
	END IF;
	
	PROC: BEGIN
	
	IF (p_operation = 'ADD') THEN
		
		-- Add new sales count record.
		INSERT INTO cm_sales_count (
			 user_id
			,product_id
			,sales_count
			,sales_count_since
			,sales_count_until)
		VALUES(
			 p_user_id
			,p_product_id
			,p_sales_count
			,p_sales_count_since
			,p_sales_count_until);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	ELSEIF (p_operation = 'EDIT') THEN
		
		-- Update the sales_count record.
		UPDATE cm_sales_count
		SET  sales_count = p_sales_count
			,sales_count_until = p_sales_count_until
		WHERE (user_id = p_user_id) AND 
			  (product_id = p_product_id);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	END IF;
	
	END PROC;
END //
DELIMITER ;