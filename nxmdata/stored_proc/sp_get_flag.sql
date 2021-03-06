DROP FUNCTION IF EXISTS sp_get_flag;
DELIMITER //
CREATE FUNCTION sp_get_flag(
	 p_key ENUM('CALCULATE_COMMISSION_ISBUSY', 'CALCULATE_PASSUP_ISBUSY', 'GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY')
) RETURNS TEXT
BEGIN

	IF (p_key = 'CALCULATE_COMMISSION_ISBUSY') THEN
	
		RETURN (SELECT flag_value FROM cm_flags WHERE (flag_key = 'CALCULATE_COMMISSION_ISBUSY'));
	ELSEIF (p_key = 'CALCULATE_PASSUP_ISBUSY') THEN
	
		RETURN (SELECT flag_value FROM cm_flags WHERE (flag_key = 'CALCULATE_PASSUP_ISBUSY'));
	ELSEIF (p_key = 'GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY') THEN

		RETURN (SELECT flag_value FROM cm_flags WHERE (flag_key = 'GENERATE_AFFILIATE_DASHBOARD_REPORT_ISBUSY'));
	END IF;
END //
DELIMITER ;