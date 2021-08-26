DROP PROCEDURE IF EXISTS sp_set_flag;
DELIMITER //
CREATE PROCEDURE sp_set_flag(
	 IN p_key ENUM(
	 			 'CALCULATE_COMMISSION_ISBUSY'
	 			,'CALCULATE_PASSUP_ISBUSY'
	 			,'GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY')
	,IN p_value TEXT
)
BEGIN
	/* 
	Add table cm_flags if not on the database. */
	IF (sp_is_table_exists('cm_flags') = 'FALSE') THEN
		CREATE TABLE cm_flags(
			 flag_key VARCHAR(256) DEFAULT '0'
			,flag_value TEXT
			,PRIMARY KEY (flag_key)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		INSERT INTO cm_flags(flag_key, flag_value) VALUES('CALCULATE_COMMISSION_ISBUSY', 'FALSE');
		INSERT INTO cm_flags(flag_key, flag_value) VALUES('CALCULATE_PASSUP_ISBUSY', 'FALSE');
		INSERT INTO cm_flags(flag_key, flag_value) VALUES('GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY', 'FALSE');
	END IF;

	IF (p_key = 'CALCULATE_COMMISSION_ISBUSY') THEN
		
		UPDATE cm_flags
		SET flag_value = p_value
		WHERE (flag_key = 'CALCULATE_COMMISSION_ISBUSY');
	ELSEIF (p_key = 'CALCULATE_PASSUP_ISBUSY') THEN
	
		UPDATE cm_flags
		SET flag_value = p_value
		WHERE (flag_key = 'CALCULATE_PASSUP_ISBUSY');
	ELSEIF (p_key = 'GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY') THEN

		UPDATE cm_flags
		SET flag_value = p_value
		WHERE (flag_key = 'GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY');
	END IF;
END //
DELIMITER ;