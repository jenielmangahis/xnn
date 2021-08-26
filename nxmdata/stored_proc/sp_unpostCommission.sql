DROP PROCEDURE IF EXISTS sp_unpostCommission;
DELIMITER //
CREATE PROCEDURE sp_unpostCommission(
	IN p_commission_period_id INT(11)
)
BEGIN
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE v_payoutId INT(11) DEFAULT 0;
	DECLARE fetchPayout CURSOR FOR 
		SELECT cp.commission_payout_id
		FROM cm_commission_payouts AS cp
		WHERE (cp.commission_period_id = p_commission_period_id);
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
	
	PROC: BEGIN
	
	/*
	Delete previous commissions for this commission period.
	In case the user is click the generate report again. */
	OPEN fetchPayout;
	loop_i: LOOP
		FETCH fetchPayout INTO v_payoutId;
		IF (v_done) THEN
			SET v_done=FALSE;
			LEAVE loop_i;
		END IF;

		DELETE FROM cm_commission_payout_details
		WHERE (cm_commission_payout_details.commission_payout_id = v_payoutId);
	END LOOP loop_i;
	CLOSE fetchPayout;

	DELETE FROM cm_commission_payouts
	WHERE (cm_commission_payouts.commission_period_id = p_commission_period_id);
	
	END PROC;
	
END //
DELIMITER ;