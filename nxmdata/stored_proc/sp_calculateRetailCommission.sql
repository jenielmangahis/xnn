DROP PROCEDURE IF EXISTS sp_calculateRetailCommission;
DELIMITER //
CREATE PROCEDURE sp_calculateRetailCommission(
	IN p_commission_period_id INT(11)
)
BEGIN
	-- Constant commission type.
	DECLARE RETAIL INT(11) DEFAULT 			1; -- Retail Commissions
	DECLARE RETAIL_POOL INT(11) DEFAULT 	2; -- Retail Pool
	DECLARE CODED_COMM INT(11) DEFAULT 		3; -- Coded Commissions
	DECLARE CHECKMATCH_COMM INT(11) DEFAULT 4; -- Check Match Commissions
	DECLARE BARRY_COMM INT(11) DEFAULT 		5; -- Barry's Commissions
	DECLARE v_str_date VARCHAR(30) DEFAULT '';
	DECLARE v_end_date VARCHAR(30) DEFAULT '';
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE ithTotalRetailComm INT(11) DEFAULT 0;
	DECLARE ActiveUsers_id INT(11) DEFAULT 0;
	DECLARE ActiveUsers_catid INT(11) DEFAULT 0;
	DECLARE ActiveUsers_active ENUM('Yes', 'No') DEFAULT 'Yes';
	DECLARE ActiveUsers CURSOR FOR
		SELECT
			 users.id AS id
			,category.catid AS catid
			,users.active AS active
		FROM users
		INNER JOIN (
			SELECT
				 categorymap.userid AS userid
				,GROUP_CONCAT(categorymap.catid) AS catid
			FROM categorymap
			WHERE (categorymap.catid = 1000) OR (categorymap.catid = 1001)
			GROUP BY categorymap.userid
		) AS category ON (category.userid = users.id)
		WHERE (users.active = 'Yes');
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
	
	PROC: BEGIN
	
	/* Get the date range from commission period. */
	SELECT cp.start_date, cp.end_date
	FROM cm_commission_periods AS cp
	WHERE (cp.commission_period_id = p_commission_period_id)
	INTO v_str_date, v_end_date;
	
	/* Save a payout for every active users. */
	OPEN ActiveUsers;
	ActiveUsersLoop: LOOP
		FETCH ActiveUsers INTO
			 ActiveUsers_id			-- id
			,ActiveUsers_catid		-- catid
			,ActiveUsers_active;	-- active
		IF (v_done) THEN
			SET v_done = FALSE;
			LEAVE ActiveUsersLoop;
		END IF;
		
		SET ithTotalRetailComm = sp_getTotalRetailCommPerAffiliate(
									 ActiveUsers_id -- p_user_id INT(11)
									,v_str_date    	-- p_date_from VARCHAR(30)
									,v_end_date 	-- p_date_to VARCHAR(30)
								);
		IF (ithTotalRetailComm > 0) THEN
			
			SET @commissioPayoutId = 0;
			SET @userId = ActiveUsers_id;
			SET @commissionType = RETAIL;
			SET @dateFrom = v_str_date;
			SET @dateTo = v_end_date;
			CALL sp_savePayoutDetailsList(
				 p_commission_period_id -- IN p_commission_period_id INT(11)
				,@commissioPayoutId 	-- IN p_commissionPayoutId INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@commissionType 		-- IN p_commissionType INT(11)	
				,@dateFrom 				-- IN p_dateFrom VARCHAR(10)
				,@dateTo 				-- IN p_dateTo VARCHAR(10)
				,ithTotalRetailComm		-- IN p_total DECIMAL(11,2)
			);
			
		END IF;
	END LOOP ActiveUsersLoop;
	CLOSE ActiveUsers;
	
	END PROC;
	
END //
DELIMITER ;