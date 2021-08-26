DROP PROCEDURE IF EXISTS sp_save_cm_commission_orders;
DELIMITER //
CREATE PROCEDURE sp_save_cm_commission_orders(
	 IN    p_user_login INT(11)							/* The user who login.  */
	,OUT   p_rows_affected INT(11)						/*                      */
	,IN    p_operation ENUM('ADD', 'EDIT', 'DELETE') 	/*                      */
	,INOUT p_id INT(11)									/* cm_commission_orders */
	,IN    p_seller_id INT(11)							/* fields from here     */
	,IN    p_passup_sponsor_id INT(11)					/*                      */
	,IN    p_sold_to_id INT(11)							/*                      */
	,IN    p_shopping_cart_id INT(11)					/*                      */
	,IN    p_sales_count INT(11)						/*                      */
	,IN    p_commission_type INT(11)					/*                      */
	,IN    p_commission_percentage FLOAT(9,3)			/*                      */
	,IN    p_not_included INT(11)						/* to here.             */
)
BEGIN
	
	PROC: BEGIN

	/* Do not continue if the seller is not active. */
	SET @isActive = COALESCE((SELECT active FROM users WHERE (id = p_seller_id)), 'No');
	IF (@isActive = 'No') THEN
		LEAVE PROC;
	END IF;
	
	/*
	Do not continue if the seller is empty.
	Do not continue if the sold to is empty. */
	IF (p_seller_id = 0) OR 
	   (p_sold_to_id = 0) THEN
		LEAVE PROC;
	END IF;
	
	IF (p_operation = 'ADD') THEN
		
		INSERT INTO cm_commission_orders(
			 seller_id
			,passup_sponsor_id
			,sold_to_id
			,shopping_cart_id
			,sales_count
			,commission_type
			,commission_percentage
			,not_included)
		VALUES(
			 p_seller_id
			,p_passup_sponsor_id
			,p_sold_to_id
			,p_shopping_cart_id
			,p_sales_count
			,p_commission_type
			,p_commission_percentage
			,p_not_included);
		SET p_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);

	ELSEIF (p_operation = 'EDIT') THEN
		
		UPDATE cm_commission_orders
		SET seller_id = p_seller_id
			,passup_sponsor_id = p_passup_sponsor_id
			,sold_to_id = p_sold_to_id
			,shopping_cart_id = p_shopping_cart_id
			,sales_count = p_sales_count
			,commission_type = p_commission_type
			,commission_percentage = p_commission_percentage
			,not_included = p_not_included
		WHERE (id = p_id);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
		
	END IF;
	
	END PROC;
END //
DELIMITER ;