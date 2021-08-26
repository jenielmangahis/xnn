DROP PROCEDURE IF EXISTS sp_do_push_sales_count_history;
DELIMITER //
CREATE PROCEDURE sp_do_push_sales_count_history(
	 IN p_logged_on_user 	INT(11)
	,IN p_user_id 			INT(11)
	,IN p_product_id 		INT(11)
	,IN p_sales_count 		INT(11)
	,IN p_transaction_id 	INT(11)
)
BEGIN
	DECLARE v_date_as_of VARCHAR(30) DEFAULT '';
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		SELECT 'An error occur on procedure sp_do_calculate_passup.';
		SHOW ERRORS LIMIT 1;
	END;
	
	PROC: BEGIN
	
	SELECT trn.transactiondate FROM transactions AS trn 
	WHERE (trn.id = p_transaction_id)
	INTO v_date_as_of;
	
	CALL sp_save_cm_sales_count_history(
		 p_logged_on_user 	-- IN p_user_login INT(11)
		,@p_rows_affected 	-- OUT p_rows_affected INT(11)
		,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
		,@p_id 				-- INOUT p_id INT(11)
		,p_user_id 			-- IN p_user_id INT(11)
		,p_product_id 		-- IN p_product_id INT(11)
		,p_sales_count 		-- IN p_sales_count INT(11)
		,v_date_as_of 		-- IN p_date_as_of VARCHAR(30)
	);
	
	END PROC;
END //
DELIMITER ;