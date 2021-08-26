DROP PROCEDURE IF EXISTS sp_save_cm_commission_payouts;
DELIMITER //
CREATE PROCEDURE sp_save_cm_commission_payouts(
	 OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_commission_payout_id INT(10)
	,IN     p_commission_payout_type_id INT(11)
	,IN 	p_user_id INT(11)
	,IN     p_commission_period_id INT(11)
	,IN		p_level INT(11)
	,IN 	p_value VARCHAR(1048)
	,IN		p_is_paid INT(11)
)
BEGIN
	
	PROC: BEGIN

	IF (sp_is_column_exists('cm_commission_payouts', 'datestamp') = 'FALSE') THEN
		ALTER TABLE cm_commission_payouts
		ADD COLUMN datestamp VARCHAR(10) DEFAULT '' AFTER is_paid;
	END IF;
	
	IF (p_operation = 'ADD') THEN
		
		INSERT INTO cm_commission_payouts(
			 `commission_payout_type_id`
			,`user_id`
			,`commission_period_id`
			,`level`
			,`value`
			,`is_paid`
			,`datestamp`)
		VALUES(
			 p_commission_payout_type_id
			,p_user_id
			,p_commission_period_id
			,p_level
			,p_value
			,p_is_paid
			,DATE_FORMAT(NOW(), '%Y-%m-%d'));
		SET p_commission_payout_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	END IF;
	
	END PROC;
END //
DELIMITER ;