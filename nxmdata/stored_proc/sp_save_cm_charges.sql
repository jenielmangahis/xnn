DROP PROCEDURE IF EXISTS sp_save_cm_charges;
DELIMITER //
CREATE PROCEDURE sp_save_cm_charges(
	 IN 	p_user_login INT(11)
	,OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_id INT(11)
	,IN 	p_user_id INT(11)
	,IN 	p_account_id INT(11)
	,IN 	p_ref_no VARCHAR(15)
	,IN 	p_amount DECIMAL(14, 2)
	,IN		p_commision_period_id INT(11)
)
BEGIN
	
	IF (sp_is_table_exists('cm_charges') = 'FALSE') THEN
		
		CREATE TABLE cm_charges(
			 id INT NOT NULL AUTO_INCREMENT
			,user_id INT(11) DEFAULT NULL -- 1
			,account_id INT(11) DEFAULT NULL -- 1
			,ref_no VARCHAR(15) DEFAULT NULL -- 0001
			,amount DECIMAL(14, 2) DEFAULT NULL -- 000,000,000,000.00
			,datestamp VARCHAR(30) DEFAULT NULL -- 2016-01-01 00:00 PM
			,PRIMARY KEY (id)
		);
	END IF;
	
	IF (sp_is_column_exists('cm_charges', 'commision_period_id') = 'FALSE') THEN
		ALTER TABLE cm_charges
		ADD COLUMN commision_period_id INT(11) DEFAULT 0 AFTER datestamp;
	END IF;
	
	PROC: BEGIN
		
	IF (p_operation = 'ADD') THEN
		
		INSERT INTO cm_charges(
			 user_id
			,account_id
			,ref_no
			,amount
			,datestamp
			,commision_period_id)
		VALUES(
			 p_user_id -- INT(11) DEFAULT NULL
			,p_account_id -- INT(11) DEFAULT NULL
			,p_ref_no -- VARCHAR(15) DEFAULT NULL
			,p_amount -- DECIMAL(14, 2) DEFAULT NULL
			,DATE_FORMAT(NOW(), '%Y-%m-%d %h:%i %p')
			,p_commision_period_id);
		SET p_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	ELSEIF (p_operation = 'EDIT') THEN
		
		UPDATE cm_charges
		SET  user_id = p_user_id
			,account_id = p_account_id
			,ref_no = p_ref_no
			,amount = p_amount
			,datestamp = DATE_FORMAT(NOW(), '%Y-%m-%d %h:%i %p')
			,commision_period_id = p_commision_period_id
		WHERE (id = p_id);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	END IF;
	
	END PROC;
END //
DELIMITER ;