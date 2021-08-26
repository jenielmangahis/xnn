DROP PROCEDURE IF EXISTS sp_fetch_transactions;
DELIMITER //
CREATE PROCEDURE sp_fetch_transactions(
	 IN p_mode ENUM('NOT-PROCESSED')
)
BEGIN
	
	IF (p_mode = 'NOT-PROCESSED') THEN
		
		-- The cron is running every 1 minute on the server thus
		-- get the unprocessed transactions from the the list.
		-- The top is the priority.
		-- NOTE: 
		--     After the transaction is processed the is_processed is set to 1 <is_processed = 1>.
		-- --------------------------------------------------------------------------------------
		SELECT * FROM transactions 
		WHERE (COALESCE(transactions.is_processed, 0) = 0) AND
			  (transactions.itemid IN (1001, 1002, 1003, 1004, 1005, 2001, 2002, 2003))
		ORDER BY transactions.id ASC
		LIMIT 1;
	END IF;
END //
DELIMITER ;