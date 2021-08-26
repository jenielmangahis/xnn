DROP PROCEDURE IF EXISTS sp_fetch_commission_period;
DELIMITER //
CREATE PROCEDURE sp_fetch_commission_period(
	 IN p_mode ENUM('LOCKED-ONLY', 'UNLOCKED-ONLY')
	,IN p_commission_period_type_id INT(11)
)
BEGIN
	
	IF (p_mode = 'LOCKED-ONLY') THEN
		
		SELECT
			 cp.commission_period_id 					AS `id`
			,cp.commission_period_type_id				AS `commission_period_type_id`
			,DATE_FORMAT(cp.start_date, '%M %d, %Y') 	AS `start_date`
			,DATE_FORMAT(cp.end_date, '%M %d, %Y') 		AS `end_date`
			,CONCAT(
				 DATE_FORMAT(cp.start_date, '%M %d, %Y')
				,' - '
				,DATE_FORMAT(cp.end_date, '%M %d, %Y')
			) 											AS `date_range`
			,cp.locked									AS `locked`
			,cp.printed									AS `printed`
		FROM cm_commission_periods 						AS `cp`
		WHERE (cp.locked = 1) AND
			  (cp.commission_period_type_id = p_commission_period_type_id);
	ELSEIF (p_mode = 'UNLOCKED-ONLY') THEN

		SELECT
			 cp.*
			,cpt.description
		FROM cm_commission_periods AS cp 
		INNER JOIN cm_commission_period_types AS cpt ON (cpt.commission_period_type_id = cp.commission_period_type_id)
		WHERE (cp.commission_period_type_id = p_commission_period_type_id);
	END IF;
END //
DELIMITER ;