DROP PROCEDURE IF EXISTS sp_save_cm_commission_payouts_details;
DELIMITER //
CREATE PROCEDURE sp_save_cm_commission_payouts_details(
	 OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_commission_payout_detail_id INT(10)
  	,IN 	p_commission_payout_id INT(11)
  	,IN 	p_order_id INT(11)
  	,IN 	p_user_id INT(11)
  	,IN 	p_level INT(11)
  	,IN 	p_value VARCHAR(1024)
  	,IN 	p_percent DECIMAL(4,1)
  	,IN 	p_amount VARCHAR(1024)
  	,IN 	p_commission_order_id INT(11)
)
BEGIN
	
	PROC: BEGIN
	
	IF (p_operation = 'ADD') THEN
		
		INSERT INTO cm_commission_payout_details (
			 commission_payout_id
			,order_id
			,user_id
			,level
			,value
			,percent
			,amount
			,commission_order_id)
		VALUES(
			 p_commission_payout_id
			,p_order_id
			,p_user_id
			,p_level
			,p_value
			,p_percent
			,p_amount
			,p_commission_order_id);
		SET p_commission_payout_detail_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	END IF;
	
	END PROC;
END //
DELIMITER ;