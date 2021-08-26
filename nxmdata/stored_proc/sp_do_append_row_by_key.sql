DROP PROCEDURE IF EXISTS sp_do_append_row_by_key;
DELIMITER //
CREATE PROCEDURE sp_do_append_row_by_key(
	 IN p_logged_on_user INT(11)	-- Logged user.
	,IN p_table_name TEXT 			-- Table name.
	,IN p_columns TEXT 				-- Columns to be retreive.
	,IN p_columns_where_clause TEXT -- Columns on WHERE clause (Comma separated without space after comma).
	,IN p_values_where_clause TEXT 	-- Values for columns in WHERE clause. */
	,OUT p_out_param TEXT 			-- Output comma separated result. 
)
BEGIN
	DECLARE v_length INT(11) DEFAULT 0;
	DECLARE v_i INT(11) DEFAULT 0;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		SELECT 'An error occur on procedure sp_do_append_row_by_key.';
		SHOW ERRORS LIMIT 1;
	END;
	
	SET v_length = (LENGTH(p_columns) - LENGTH(REPLACE(p_columns, ',', ''))) + 1;
	SET v_i = 0;
	SET @columns = 'CONCAT(';
	THIS_LOOP: BEGIN
	WHILE (v_i <= v_length) DO
		IF (v_i = 0) THEN
			SET @columns = CONCAT(@columns, sp_get_element_at(p_columns, v_i));
		ELSEIF (v_i = v_length) THEN
			SET @columns = CONCAT(@columns, sp_get_element_at(p_columns, v_i), ')');
		ELSE
			SET @columns = CONCAT(@columns, ',', sp_get_element_at(p_columns, v_i));
		END IF;
		SET v_i = v_i + 1;
	END WHILE;
	END THIS_LOOP;
	SET @columns = REPLACE(@columns, ',', ', '','', ');
	
	SET v_length = (LENGTH(p_columns_where_clause) - LENGTH(REPLACE(p_columns_where_clause, ',', ''))) + 1;
	SET v_i = 0;
	SET @where_clause = ' WHERE ';
	THIS_LOOP: BEGIN
	WHILE (v_i < v_length) DO
		IF (v_i = 0) THEN
			SET @where_clause = CONCAT(
					 @where_clause
					,'('
					,sp_get_element_at(p_columns_where_clause, v_i)
					,' = '
					,sp_get_element_at(p_values_where_clause, v_i)
					,')'
				);
		ELSEIF (v_i = v_length) THEN
			SET @where_clause = CONCAT(
					 @where_clause
					,' AND ('
					,sp_get_element_at(p_columns_where_clause, v_i)
					,' = '
					,sp_get_element_at(p_values_where_clause, v_i)
					,')'
				);
		ELSE
			SET @where_clause = CONCAT(
					 @where_clause
					,' AND ('
					,sp_get_element_at(p_columns_where_clause, v_i)
					,' = '
					,sp_get_element_at(p_values_where_clause, v_i)
					,')'
				);
		END IF;
		SET v_i = v_i + 1;
	END WHILE;
	END THIS_LOOP;	
	
 	SET @result_row = '';
	SET @sql_stmt = CONCAT('SELECT ', @columns ,' FROM ', p_table_name , @where_clause, ' LIMIT 1 ', ' INTO @result_row');
	PREPARE stmt FROM @sql_stmt;
	EXECUTE stmt;
	DEALLOCATE PREPARE stmt;
	
	SET p_out_param = COALESCE(@result_row, '');
END //
DELIMITER ;