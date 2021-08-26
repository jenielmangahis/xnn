DROP PROCEDURE IF EXISTS sp_calculateRetailPoolCommission;
DELIMITER //
CREATE PROCEDURE sp_calculateRetailPoolCommission(
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
	DECLARE v_CI INT(11) DEFAULT 0;
	DECLARE v_totalCI INT(11) DEFAULT 0;
	DECLARE v_totalCV DECIMAL(14, 2) DEFAULT  00.00; -- 000,000,000,000.00;
	DECLARE retailPool DECIMAL(14, 2) DEFAULT  00.00; -- 000,000,000,000.00;
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
	SELECT
		 cp.start_date
		,cp.end_date
	FROM cm_commission_periods AS cp
	WHERE (cp.commission_period_id = p_commission_period_id)
	INTO v_str_date, v_end_date;
	
	/* Get the total CI and CV base from the date range from the period. */
	SET v_totalCI = sp_getTotalCI(v_str_date, v_end_date);
	SET v_totalCV = sp_getTotalCV(v_str_date, v_end_date);
	
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
		
		SET v_CI = sp_getTotalCIPerAffiliate(v_str_date, v_end_date, ActiveUsers_id);
		SET retailPool = (((v_totalCV * 0.50) / v_totalCI) * v_CI);
		IF (retailPool > 0) THEN
			
			SET @commissioPayoutId = @newKey;
			SET @userId = ActiveUsers_id;
			SET @commissionType = RETAIL_POOL;
			SET @dateFrom = v_str_date;
			SET @dateTo = v_end_date;
			SET @total = retailPool;
			CALL sp_savePayoutDetailsList(
				 p_commission_period_id -- IN p_commission_period_id INT(11)
				,@commissioPayoutId 	-- IN p_commissionPayoutId INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@commissionType 		-- IN p_commissionType INT(11)	
				,@dateFrom 				-- IN p_dateFrom VARCHAR(10)
				,@dateTo 				-- IN p_dateTo VARCHAR(10)
				,@total					-- IN p_total DECIMAL(11,2)
			);
		END IF;
	END LOOP ActiveUsersLoop;
	CLOSE ActiveUsers;
	
	END PROC;
	
END //
DELIMITER ;