DROP FUNCTION IF EXISTS sp_isCheckmatchQualifiedByDate;
DELIMITER //
CREATE FUNCTION sp_isCheckmatchQualifiedByDate(
	 p_user_id INT(11)				/* User to be evaluated. */
	,p_transaction_date VARCHAR(10)	/* The date to be check in range 2016-02-01. */
) RETURNS ENUM('TRUE', 'FALSE')
BEGIN
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE CheckMatchDetails_from VARCHAR(10) DEFAULT '';
	DECLARE CheckMatchDetails_to VARCHAR(10) DEFAULT '';
	DECLARE CheckMatchDetails_enabled ENUM('Yes', 'No') DEFAULT 'No';
	DECLARE CheckMatchDetails CURSOR FOR 
		SELECT
			 cmd.date_from			AS `from`
			,cmd.date_to			AS `to`
			,cmd.enabled			AS `enabled`
		FROM cm_checkmatch_details 	AS cmd
		WHERE (cmd.checkmatch_id = (
			SELECT cm.id FROM cm_checkmatch AS cm
			WHERE (cm.user_id = p_user_id)
		));
		
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
	
	IF (p_user_id = sp_GLOBAL('ADMIN')) THEN
		
		RETURN 'TRUE';
	ELSE
		
		SET @IsFound = 'FALSE';
		OPEN CheckMatchDetails;
		CheckMatchDetails_Loop: LOOP
			FETCH CheckMatchDetails INTO
				 CheckMatchDetails_from
				,CheckMatchDetails_to
				,CheckMatchDetails_enabled;
			IF (v_done) THEN
				SET v_done = FALSE;
				LEAVE CheckMatchDetails_Loop; 
			END IF;
			
			IF (p_transaction_date >= CheckMatchDetails_from) AND 
			   (p_transaction_date <= CheckMatchDetails_to) AND
			   (CheckMatchDetails_enabled = 'Yes') THEN
				SET @IsFound = 'TRUE';
			END IF;
		END LOOP CheckMatchDetails_Loop;
		CLOSE CheckMatchDetails;	
	END IF;
		
	RETURN @IsFound;
END //
DELIMITER ;