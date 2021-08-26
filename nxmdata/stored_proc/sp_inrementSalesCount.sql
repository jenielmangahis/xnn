DROP PROCEDURE IF EXISTS sp_inrementSalesCount;
DELIMITER //
CREATE PROCEDURE sp_inrementSalesCount(
	 IN p_logged_on_user INT(11)
	,IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
	,IN p_user_id INT(11)
	,IN p_product_id INT(11)
	,IN p_datestamp VARCHAR(30)
)
BEGIN
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE v_sales_count INT(11) DEFAULT 0;
	DECLARE v_ith_id INT(11) DEFAULT 0;
	DECLARE v_ith_user_id INT(11) DEFAULT 0;
	DECLARE v_ith_sales_count INT(11) DEFAULT 0;	
	DECLARE fetch_i CURSOR FOR
		SELECT
			 scp.id								AS id
			,p_user_id							AS user_id
			,COALESCE(csc.sales_count, -9999) 	AS sales_count
		FROM shoppingcart_products AS scp
		LEFT JOIN (
			SELECT
				 cm_sales_count.product_id
				,cm_sales_count.sales_count
				,cm_sales_count.user_id
			FROM cm_sales_count
			WHERE (cm_sales_count.user_id = p_user_id)
		) AS csc ON (csc.product_id = scp.id)
		ORDER BY scp.id ASC;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
	
	OPEN fetch_i;
    for_i: LOOP
        FETCH fetch_i INTO
             v_ith_id
            ,v_ith_user_id
            ,v_ith_sales_count;
        IF v_done THEN
            SET v_done = FALSE;
            LEAVE for_i;
        END IF;
		
		-- Sentinnel value -9999 means that this product(p_product_id) of user(p_user_id) is not found 
		-- on the cm_sales_count table.
		-- Thus we implicitly add it with -1 initial value.
		IF (v_ith_sales_count = -9999) THEN
			
			IF (p_user_id = sp_GLOBAL('ADMIN')) THEN
				
				/*
				SKU 2001 it counts as a sales count for 1001.
				SKU 2002 it counts as a sales count for 1002.
				SKU 2003 it counts as a sales count for 1003. */
				IF (v_ith_id = 2001) THEN
					
					IF (EXISTS((
							 SELECT * FROM cm_sales_count
							 WHERE (cm_sales_count.user_id = v_ith_user_id) AND 
								   (cm_sales_count.product_id = 1001))) = 0) THEN
						CALL sp_save_cm_sales_count(
							 p_logged_on_user	-- IN p_logged_on_user INT(11)
							,@rows_affected 	-- OUT p_rows_affected INT(11)
							,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
							,v_ith_user_id 		-- IN p_user_id INT(11)
							,1001 				-- IN p_product_id INT(11)
							,0 					-- IN p_sales_count INT(11)
							,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
							,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
						);
					END IF;
				ELSEIF (v_ith_id = 2002) THEN
					
					IF (EXISTS((
							 SELECT * FROM cm_sales_count
							 WHERE (cm_sales_count.user_id = v_ith_user_id) AND 
								   (cm_sales_count.product_id = 1002))) = 0) THEN
						CALL sp_save_cm_sales_count(
							 p_logged_on_user	-- IN p_logged_on_user INT(11)
							,@rows_affected 	-- OUT p_rows_affected INT(11)
							,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
							,v_ith_user_id 		-- IN p_user_id INT(11)
							,1002 				-- IN p_product_id INT(11)
							,0 					-- IN p_sales_count INT(11)
							,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
							,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
						);
					END IF;
				ELSEIF (v_ith_id = 2003) THEN
					
					IF (EXISTS((
							 SELECT * FROM cm_sales_count
							 WHERE (cm_sales_count.user_id = v_ith_user_id) AND 
								   (cm_sales_count.product_id = 1003))) = 0) THEN
						CALL sp_save_cm_sales_count(
							 p_logged_on_user	-- IN p_logged_on_user INT(11)
							,@rows_affected 	-- OUT p_rows_affected INT(11)
							,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
							,v_ith_user_id 		-- IN p_user_id INT(11)
							,1003 				-- IN p_product_id INT(11)
							,0 					-- IN p_sales_count INT(11)
							,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
							,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
						);
					END IF;
				END IF; /* IF (v_ith_id = 2001) THEN */
				
				/* 
				Admin account is 10.
				Admin sales count start with 0 not -1 to denote that admin is buy any product 
				initially. */
				CALL sp_save_cm_sales_count(
					 p_logged_on_user	-- IN p_logged_on_user INT(11)
					,@rows_affected 	-- OUT p_rows_affected INT(11)
					,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,v_ith_user_id 		-- IN p_user_id INT(11)
					,v_ith_id 			-- IN p_product_id INT(11)
					,0 					-- IN p_sales_count INT(11)
					,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
					,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
				);
				SET v_ith_sales_count = 0;
			ELSE
				
				/*
				sku 2001 it counts as a sales count for 1001.
				sku 2002 it counts as a sales count for 1002.
				sku 2003 it counts as a sales count for 1003. */
				IF (v_ith_id = 2001) THEN
					
					IF (EXISTS((SELECT * FROM cm_sales_count
							    WHERE (cm_sales_count.user_id = v_ith_user_id) AND 
								      (cm_sales_count.product_id = 1001))) = 0) THEN
						CALL sp_save_cm_sales_count(
							 p_logged_on_user	-- IN p_logged_on_user INT(11)
							,@rows_affected 	-- OUT p_rows_affected INT(11)
							,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
							,v_ith_user_id 		-- IN p_user_id INT(11)
							,1001 				-- IN p_product_id INT(11)
							,-1 					-- IN p_sales_count INT(11)
							,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
							,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
						);
					END IF;
				ELSEIF (v_ith_id = 2002) THEN
					
					IF (EXISTS((SELECT * FROM cm_sales_count
							    WHERE (cm_sales_count.user_id = v_ith_user_id) AND 
								      (cm_sales_count.product_id = 1002))) = 0) THEN
						CALL sp_save_cm_sales_count(
							 p_logged_on_user	-- IN p_logged_on_user INT(11)
							,@rows_affected 	-- OUT p_rows_affected INT(11)
							,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
							,v_ith_user_id 		-- IN p_user_id INT(11)
							,1002 				-- IN p_product_id INT(11)
							,-1 					-- IN p_sales_count INT(11)
							,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
							,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
						);
					END IF;
				ELSEIF (v_ith_id = 2003) THEN
					
					IF (EXISTS((SELECT * FROM cm_sales_count
							    WHERE (cm_sales_count.user_id = v_ith_user_id) AND 
								      (cm_sales_count.product_id = 1003))) = 0) THEN
						CALL sp_save_cm_sales_count(
							 p_logged_on_user	-- IN p_logged_on_user INT(11)
							,@rows_affected 	-- OUT p_rows_affected INT(11)
							,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
							,v_ith_user_id 		-- IN p_user_id INT(11)
							,1003 				-- IN p_product_id INT(11)
							,-1 				-- IN p_sales_count INT(11)
							,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
							,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
						);
					END IF;
				END IF;
				
				/*
				Other account is any other than 10.
				Every account sales count start with -1 sales count. */
				CALL sp_save_cm_sales_count(
					 p_logged_on_user	-- IN p_logged_on_user INT(11)
					,@rows_affected 	-- OUT p_rows_affected INT(11)
					,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,v_ith_user_id 		-- IN p_user_id INT(11)
					,v_ith_id 			-- IN p_product_id INT(11)
					,-1 				-- IN p_sales_count INT(11)
					,p_datestamp 		-- IN p_sales_count_since VARCHAR(30)
					,p_datestamp 		-- IN p_sales_count_until VARCHAR(30)
				);
				SET v_ith_sales_count = -1;
			END IF;
		END IF;
		
		IF (v_ith_id = p_product_id) THEN
			
			/*
			sku 2001 it counts as a sales count for 1001.
			sku 2002 it counts as a sales count for 1002.
			sku 2003 it counts as a sales count for 1003. */
			IF (v_ith_id = 2001) THEN
				
				CALL sp_save_cm_sales_count(
					 p_logged_on_user		-- IN p_logged_on_user INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,'EDIT' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,v_ith_user_id 			-- IN p_user_id INT(11)
					,1001 					-- IN p_product_id INT(11)
					,v_ith_sales_count + 1 	-- IN p_sales_count INT(11)
					,p_datestamp 			-- IN p_sales_count_since VARCHAR(30)
					,p_datestamp 			-- IN p_sales_count_until VARCHAR(30)
				);
			ELSEIF (v_ith_id = 2002) THEN
				
				CALL sp_save_cm_sales_count(
					 p_logged_on_user		-- IN p_logged_on_user INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,'EDIT' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,v_ith_user_id 			-- IN p_user_id INT(11)
					,1002 					-- IN p_product_id INT(11)
					,v_ith_sales_count + 1 	-- IN p_sales_count INT(11)
					,p_datestamp 			-- IN p_sales_count_since VARCHAR(30)
					,p_datestamp 			-- IN p_sales_count_until VARCHAR(30)
				);
			ELSEIF (v_ith_id = 2003) THEN
				
				CALL sp_save_cm_sales_count(
					 p_logged_on_user		-- IN p_logged_on_user INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,'EDIT' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,v_ith_user_id 			-- IN p_user_id INT(11)
					,1003 					-- IN p_product_id INT(11)
					,v_ith_sales_count + 1 	-- IN p_sales_count INT(11)
					,p_datestamp 			-- IN p_sales_count_since VARCHAR(30)
					,p_datestamp 			-- IN p_sales_count_until VARCHAR(30)
				);
			END IF;
			
			CALL sp_save_cm_sales_count(
				 p_logged_on_user		-- IN p_logged_on_user INT(11) 
				,@rows_affected 		-- OUT p_rows_affected INT(11)
				,'EDIT' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,v_ith_user_id 			-- IN p_user_id INT(11)
				,v_ith_id 				-- IN p_product_id INT(11)
				,v_ith_sales_count + 1 	-- IN p_sales_count INT(11)
				,p_datestamp 			-- IN p_sales_count_since VARCHAR(30)
				,p_datestamp 			-- IN p_sales_count_until VARCHAR(30)
			);
		END IF;
	END LOOP for_i;
	CLOSE fetch_i;
	
END //
DELIMITER ;