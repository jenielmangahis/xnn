DROP PROCEDURE IF EXISTS sp_calculateCheckmatchCommission;
DELIMITER //
CREATE PROCEDURE sp_calculateCheckmatchCommission(
	IN p_commission_period_id INT(11)
)
BEGIN
	DECLARE RETAIL INT(11) DEFAULT 1; -- Retail Commissions
	DECLARE RETAIL_POOL INT(11) DEFAULT 2; -- Retail Pool
	DECLARE CODED_COMM INT(11) DEFAULT 3; -- Coded Commissions
	DECLARE CHECKMATCH_COMM INT(11) DEFAULT 4; -- Check Match Commissions
	DECLARE BARRY_COMM INT(11) DEFAULT 5; -- Barry's Commissions
	DECLARE AFFILLIATE INT(11) DEFAULT 1001; -- Constant for affiliate.
	DECLARE v_commissionValue INT(11) DEFAULT 0;
	DECLARE v_startDate VARCHAR(30) DEFAULT '';
	DECLARE v_endDate VARCHAR(30) DEFAULT '';
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE v_ithId INT(11) DEFAULT 0;
	DECLARE v_ithCatId INT(11) DEFAULT 0;
	DECLARE v_ithActive ENUM('Yes', 'No') DEFAULT 'Yes';
	DECLARE fetchUsers CURSOR FOR
		SELECT
			 users.id 		AS id
			,category.catid AS catid
			,users.active 	AS active
		FROM users
		INNER JOIN (
			SELECT
				 categorymap.userid 				AS userid
				,GROUP_CONCAT(categorymap.catid) 	AS catid
			FROM categorymap
			WHERE (categorymap.catid = 1001)
			GROUP BY categorymap.userid
		) AS category ON (category.userid = users.id)
		WHERE (users.active = 'Yes');
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
	
	PROC: BEGIN
	
	/* Get the date range from commission period. */
	SELECT 
		 cp.start_date 	AS start_date
		,cp.end_date	AS end_date
	FROM cm_commission_periods AS cp
	WHERE (cp.commission_period_id = p_commission_period_id)
	INTO v_startDate, v_endDate;
	
	/* Save a payout for every active users. */
	OPEN fetchUsers;
	fetchUsersLoop: LOOP
		FETCH fetchUsers INTO
			 v_ithId		-- id
			,v_ithCatId		-- catid
			,v_ithActive;	-- active
		IF (v_done) THEN
			SET v_done = FALSE;
			LEAVE fetchUsersLoop;
		END IF;
		
		SET v_commissionValue = sp_getTotalCheckmatchCommPerAffiliate(
									 v_ithId 		-- p_user_id INT(11)
									,v_startDate 	-- p_date_from VARCHAR(30)
									,v_endDate 		-- p_date_to VARCHAR(30)
								);
		IF (v_commissionValue > 0) THEN
			
			SET @commissioPayoutId = @newKey;
			SET @userId = v_ithId;
			SET @commissionType = CHECKMATCH_COMM;
			SET @dateFrom = v_startDate;
			SET @dateTo = v_endDate;
			CALL sp_savePayoutDetailsList(
				 p_commission_period_id 	-- IN p_commission_period_id INT(11)
				,@commissioPayoutId 		-- IN p_commissionPayoutId INT(11)
				,@userId 					-- IN p_user_id INT(11)
				,@commissionType 			-- IN p_commissionType INT(11)	
				,@dateFrom 					-- IN p_dateFrom VARCHAR(10)
				,@dateTo 					-- IN p_dateTo VARCHAR(10)
				,0.00						-- IN p_total DECIMAL(11,2)
			);

		END IF;
	END LOOP fetchUsersLoop;
	CLOSE fetchUsers;

	END PROC;
	
END //
DELIMITER ;