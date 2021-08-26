DROP FUNCTION IF EXISTS sp_getPassupPhase;
DELIMITER //
CREATE FUNCTION sp_getPassupPhase(
	p_sales_count INT(11) -- The current sales count.
) RETURNS ENUM('NONE', 'PHASE_1', 'PHASE_2')
BEGIN
	/*
	PRE-CONDITION:
		p_sales_count
	POST-CONDITION:
		   Phase 1(sales 1-6): Sales 2, 4, 6, create “Pass-UP” commissions for the 
		affiliates product sponsor. Sales 1, 3, 5, on any product type do not create a pass-up commission.
		Phase 2(sales 7+): Sales 7,8,9,10 do not create a pass-up commission. Every 5th sale does, so sale 11, 16,
		21, 26, etc all create Phase 2 pass-ups. 
		   If the product sponsor is not Phase 2 qualified, the system looks at
		the affiliate who personally made the sale. If the affiliate that personally made the sale if Phase 2 
		qualified then that new affiliate will be coded to them. If the affiliate is not Phase 2 qualified 
		either, then the sponsor becomes the MASTER account ID 3 for this purchase. */
	
	DECLARE v_return_value ENUM('NONE', 'PHASE_1', 'PHASE_2') DEFAULT 'NONE';
	DECLARE v_i INT(11) DEFAULT 0;
	
	IF ((p_sales_count = 0) OR (p_sales_count = -1)) THEN
	
		SET v_return_value = 'NONE';
	ELSEIF ((p_sales_count >= 1) AND (p_sales_count <= 6)) THEN
		
		IF ((p_sales_count % 2) = 0) THEN
		
			SET v_return_value = 'PHASE_1';
		ELSE
		
			SET v_return_value = 'NONE';
		END IF;		
	ELSEIF (p_sales_count > 6) THEN
		
		BLK: BEGIN
		SET v_return_value = 'NONE';
		SET v_i = 6;
		WHILE (v_i <= p_sales_count) DO
		
			IF (p_sales_count = v_i) THEN
			
				SET v_return_value = 'PHASE_2';
				LEAVE BLK;
			END IF;
			
			SET v_i = v_i + 5;
		END WHILE;
		END BLK;
		
	END IF;
	
	RETURN v_return_value;
END //
DELIMITER ;