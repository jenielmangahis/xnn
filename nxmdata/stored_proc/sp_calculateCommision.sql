DROP PROCEDURE IF EXISTS sp_calculateCommision;
DELIMITER //
CREATE PROCEDURE sp_calculateCommision(
	 IN p_logged_on_user INT(11)
	,IN p_commission_type ENUM(
							 'MONTHLY-BARRY'
							,'RETAIL-COMMISSION'
							,'RETAIL-POOL-COMMISSION'
							,'CHECKMATCH-COMMISSION'
							,'CODED-COMMISSION'
						  )
	,IN p_commission_period_id INT(11)
)
BEGIN
	
	IF (p_commission_type = 'RETAIL-COMMISSION') THEN
		
		/*
		Save new payout and payout details.*/
		CALL sp_calculateRetailCommission(p_commission_period_id);
	ELSEIF (p_commission_type = 'RETAIL-POOL-COMMISSION') THEN
		
		/*
		Save new payout and payout details.*/
		CALL sp_calculateRetailPoolCommission(p_commission_period_id);
	ELSEIF (p_commission_type = 'CHECKMATCH-COMMISSION') THEN
		
		/*
		Save new payout and payout details.*/
		CALL sp_calculateCheckmatchCommission(p_commission_period_id);
	ELSEIF (p_commission_type = 'CODED-COMMISSION') THEN
		
		/*
		Save new payout and payout details.*/
		CALL sp_calculateCodedCommission(p_commission_period_id);
	ELSEIF (p_commission_type = 'MONTHLY-BARRY') THEN
		
		/*
		Save new payout and payout details.*/
		CALL sp_calculateBarryCommission(p_commission_period_id);
	END IF;
	
END //
DELIMITER ;