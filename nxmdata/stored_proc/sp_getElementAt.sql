DROP FUNCTION IF EXISTS sp_getElementAt;
DELIMITER //
CREATE FUNCTION sp_getElementAt(
	 p_string TEXT 		/* Comma separated values. */
	,p_index INT(11) 	/* 0 base index. */
) RETURNS TEXT
BEGIN
	/*
	This function accept a comma separated values, and return a value specified by index.
	Example.
		sp_getElementAt('foo,bar,foobar', 0)
		will return 'foo'. */
	DECLARE v_length INT(11) DEFAULT 0;
	DECLARE v_i INT(11) DEFAULT 0;
	DECLARE v_ith TEXT DEFAULT '';
	
	THIS_FUNCTION: BEGIN
	
	SET v_length = (LENGTH(p_string) - LENGTH(REPLACE(p_string, ',', ''))) + 1;
	IF (p_index > (v_length -1)) THEN
		LEAVE THIS_FUNCTION;
	END IF;
	
	THIS_LOOP: BEGIN
	
	SET v_i = 0;
	WHILE (v_i < v_length) DO
		SET v_ith = TRIM(SUBSTRING_INDEX(p_string, ',', 1));
		SET p_string = RIGHT(p_string, LENGTH(p_string) - LENGTH(CONCAT(v_ith, ',')));
		IF ((v_i - 1) = p_index) THEN
			LEAVE THIS_LOOP;
		END IF;
		SET v_i = v_i + 1;
	END WHILE;
	END THIS_LOOP;
		
	END THIS_FUNCTION;
	
	RETURN COALESCE(v_ith, '');
END //
DELIMITER ;