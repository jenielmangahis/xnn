DROP FUNCTION IF EXISTS sp_getSalesCount;
DELIMITER //
CREATE FUNCTION sp_getSalesCount(
	 p_user_id INT(11)			/* -1 Denote all users. */
	,p_product_id INT(11)		/*  */
) RETURNS INT(11)
BEGIN
	DECLARE v_sales_count INT(11) DEFAULT 0;
	DECLARE v_no_of_days INT(11) DEFAULT 0;
	DECLARE v_return_value INT(11) DEFAULT 0;
	
	SET v_sales_count = COALESCE(
							(
								SELECT tsc.sales_count
								FROM cm_sales_count AS tsc
								WHERE (tsc.user_id = p_user_id) AND 
									  (tsc.product_id = p_product_id)
							)
							,-1
						);
	
	IF ((v_sales_count < 0) AND (p_user_id = sp_GLOBAL('ADMIN'))) THEN
		
		SET v_return_value = 0;
	ELSE
	
		SET v_no_of_days = COALESCE(
								(
								 SELECT DATEDIFF(tsc.sales_count_until, tsc.sales_count_since) 
								 FROM cm_sales_count AS tsc
								 WHERE (tsc.user_id = p_user_id) AND 
									   (tsc.product_id = p_product_id)
								)
								,0
							) + 1;
		IF ((v_sales_count = 6) AND (v_no_of_days <= 30)) THEN

			-- sales count 6 in less than 30 days will be treated as 
			-- 12 sales count.
			SET v_return_value = 12;
		ELSE

			SET v_return_value = v_sales_count;
		END IF;
	END IF;
	
	RETURN v_return_value;
END //
DELIMITER ;