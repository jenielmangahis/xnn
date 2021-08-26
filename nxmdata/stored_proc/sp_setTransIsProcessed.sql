DROP PROCEDURE IF EXISTS sp_setTransIsProcessed;
DELIMITER //
CREATE PROCEDURE sp_setTransIsProcessed(
	IN p_transaction_id INT(11)
)
BEGIN
	UPDATE transactions
	SET transactions.is_processed = 1
	WHERE transactions.id = p_transaction_id;
END //
DELIMITER ;