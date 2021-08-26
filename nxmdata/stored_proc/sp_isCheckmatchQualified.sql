DROP FUNCTION IF EXISTS sp_isCheckmatchQualified;
DELIMITER //
CREATE FUNCTION sp_isCheckmatchQualified(
	 p_user_id INT(11)
) RETURNS ENUM('TRUE', 'FALSE')
BEGIN
	
	IF (p_user_id = sp_GLOBAL('ADMIN')) THEN
		
		RETURN 'TRUE';
	ELSE
		
		RETURN 
			COALESCE(
				(
				 SELECT
					CASE WHEN (cm.is_qualified = 'Yes') 
						THEN 'TRUE' 
						ELSE 'FALSE' 
					END AS is_qualified
				 FROM cm_checkmatch AS cm
				 WHERE (cm.user_id = p_user_id)
				 LIMIT 1
				)
				,'FALSE'
			);	
	END IF;
END //
DELIMITER ;