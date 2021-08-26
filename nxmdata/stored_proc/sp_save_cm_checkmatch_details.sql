DROP PROCEDURE IF EXISTS sp_save_cm_checkmatch_details;
DELIMITER //
CREATE PROCEDURE sp_save_cm_checkmatch_details(
	 IN 	p_user_login INT(11)
	,OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_id INT(11)
	,IN 	p_checkmatch_id INT(11)
	,IN 	p_date_from DATE
	,IN 	p_date_to DATE
	,IN 	p_transaction_id INT(11)
	,IN     p_enabled ENUM('Yes', 'No')
)
BEGIN
	
	IF (sp_is_table_exists('cm_checkmatch_details') = 'FALSE') THEN
		
		CREATE TABLE cm_checkmatch_details (
			 id 			INT(11) NOT NULL AUTO_INCREMENT
			,checkmatch_id 	INT(11) NOT NULL
			,date_from 		DATE DEFAULT NULL
			,date_to 		DATE DEFAULT NULL
			,transaction_id INT(11) DEFAULT NULL
			,enabled 		ENUM('Yes','No') DEFAULT 'No'
			,PRIMARY KEY (id, checkmatch_id)
		) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=latin1;
	END IF;
	
	PROC: BEGIN
		
	IF (p_operation = 'ADD') THEN
		
		SET p_id = 
				COALESCE(
					(
					 SELECT MAX(cm_checkmatch_details.id) AS last_id
					 FROM cm_checkmatch_details
					 WHERE (cm_checkmatch_details.checkmatch_id = p_checkmatch_id)
					)
					,0
				);
		SET p_id = p_id  + 1;
		INSERT INTO cm_checkmatch_details(
			 id
			,checkmatch_id
			,date_from
			,date_to
			,transaction_id
			,enabled)
		VALUES(
		     p_id
			,p_checkmatch_id
			,p_date_from
			,p_date_to
			,p_transaction_id
			,p_enabled);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	END IF;
	
	END PROC;
END //
DELIMITER ;