DROP FUNCTION IF EXISTS sp_GLOBAL;
DELIMITER //
CREATE FUNCTION sp_GLOBAL(
	p_key ENUM('BARRY', 'ADMIN')
) RETURNS TEXT
BEGIN

	IF (p_key = 'BARRY') THEN
		
		RETURN 
			COALESCE(
				(
				 SELECT cm_flags.flag_value
				 FROM cm_flags
				 WHERE (cm_flags.flag_key = p_key)
				)
				,''
			);
	ELSEIF (p_key = 'ADMIN') THEN
	
		RETURN 
			COALESCE(
				(
				 SELECT cm_flags.flag_value
				 FROM cm_flags
				 WHERE (cm_flags.flag_key = p_key)
				)
				,''
			);
	ELSE 
		RETURN '';
	END IF;
END //
DELIMITER ;