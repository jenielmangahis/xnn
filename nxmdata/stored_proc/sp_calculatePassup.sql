DROP PROCEDURE IF EXISTS sp_calculatePassup;
DELIMITER //
CREATE PROCEDURE sp_calculatePassup(
	 IN p_logged_on_user INT(11)
	,IN p_transaction_id INT(11)
)
BEGIN
	/*
	PRE-CONDITION: 
		1. The table cm_commission_period_types must have ff. values.
		   These values are similar to the commission type constant.
			('1', 'Retail Commissions', 'Retail Commissions', 'daily', '1')
			('2', 'Retail Pool', 'Retail Pool', 'monthly', '1')
			('3', 'Coded Commissions', 'Coded Commissions', 'daily', '1')
			('4', 'Check Match Commissions', 'Check Match Commissions', 'daily', '1')
			('5', 'Barry\'s Commissions', 'Barry\'s Commissions', 'monthly', '1')

	Requires.
	 		1. sp_get_passup_phase.
	 		2. sp_inrementSalesCount.
			3. sp_get_qualified.
			4. sp_getAbove.
			5. sp_save_cm_product_sponsors.
			6. sp_save_cm_commission_orders.
			7. sp_save_cm_nodes. 
	
	1000 = Free member.
	1001 = Pro-member. */
	
	-- Constant commission type.
	DECLARE RETAIL INT(11) DEFAULT 0; -- Retail Commissions
	DECLARE RETAIL_POOL INT(11) DEFAULT 0; -- Retail Pool
	DECLARE CODED_COMM INT(11) DEFAULT 0; -- Coded Commissions
	DECLARE CHECKMATCH_COMM INT(11) DEFAULT 0; -- Check Match Commissions
	DECLARE BARRY_COMM INT(11) DEFAULT 0; -- Barry's Commissions
	-- Constant naxum admin.
	DECLARE NAXUM_ADMIN INT(11) DEFAULT 0; -- naxum account.
	-- Constant for two type of members.
	DECLARE FREE_MEMBER INT(11) DEFAULT 0;
	DECLARE AFFILIATE INT(11) DEFAULT 0;
	
	DECLARE v_level INT(11) DEFAULT 0;
	DECLARE v_seller INT(11) DEFAULT 0;
	DECLARE v_buyer INT(11) DEFAULT 0;
	DECLARE v_sku INT(11) DEFAULT 0;
	DECLARE v_datetime VARCHAR(30) DEFAULT '';
	DECLARE v_passupPhase ENUM('NONE', 'PHASE_1', 'PHASE_2', 'NEGATIVE_ONE');
	DECLARE v_passupQualified INT(11) DEFAULT 0;
	DECLARE v_catId INT(11) DEFAULT 1000;
	DECLARE v_salesCount INT(11) DEFAULT 0;
	DECLARE v_firstDayOfTheMonth VARCHAR(10) DEFAULT '';
	DECLARE v_productSponsor INT(11) DEFAULT 0;
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		GET DIAGNOSTICS CONDITION 1 @sqlstate = RETURNED_SQLSTATE, @errno = MYSQL_ERRNO, @text = MESSAGE_TEXT;
		
		SET @full_error = CONCAT("ERROR ", @errno, " (", @sqlstate, "): ", @text);
		SET @param = CONCAT('sp_calculatePassup(', p_logged_on_user, ', ', p_transaction_id, ')');
		
		INSERT INTO cm_error_log(description, datetime_stamp, location)
		VALUES(@full_error, NOW(), @param);
	END;
	
	PROC: BEGIN
	
	/*
	Get the value of the global variables.*/
	SET RETAIL = sp_GLOBAL('RETAIL'); 					-- Retail Commissions
	SET RETAIL_POOL = sp_GLOBAL('RETAIL_POOL'); 		-- Retail Pool
	SET CODED_COMM = sp_GLOBAL('CODED_COMM'); 			-- Coded Commissions
	SET CHECKMATCH_COMM = sp_GLOBAL('CHECKMATCH_COMM'); -- Check Match Commissions
	SET BARRY_COMM = sp_GLOBAL('BARRY_COMM'); 			-- Barry's Commissions
	SET NAXUM_ADMIN = sp_GLOBAL('NAXUM_ADMIN'); 		-- naxum account.
	SET FREE_MEMBER = sp_GLOBAL('FREE_MEMBER');			--
	SET AFFILIATE = sp_GLOBAL('AFFILIATE');				--
	
	/*
	Do not continue if the transaction id is 0
	This is happening if no more transaction to be processed and the cron still invoke
	the procedure passing 0 transaction id. */
	IF (COALESCE(p_transaction_id, 0) <= 0) THEN
		LEAVE PROC;
	END IF;
	
	/*
	Do not continue of the current process is not finished. 
	The caller of this procedure may call this many times but once the current
	execution is not finish we do not allow another call to continue to prevent
	duplicate commission. */
	IF (sp_get_flag('CALCULATE_PASSUP_ISBUSY') = 'TRUE') THEN
		
		CALL echo(CONCAT('sp_calculatePassup is invoke while it is busy.'));
		LEAVE PROC;
	END IF;
	
	/* Set the flag to denote that this procedure is busy. */
	CALL sp_set_flag('CALCULATE_PASSUP_ISBUSY', 'TRUE');
	
	/* 
	Get the transaction record by p_transaction_id. 
	catId 1001 is affiliate and catId 1000 is a free member.
	This data is usefull on processing passup later in the code below. */
	SELECT
		 trn.userid					AS userid
		,trn.sponsorid				AS sponsorid
		,trn.itemid					AS itemid
		,trn.transactiondate		AS transactiondate
		,COALESCE(cty.catid, 1000) 	AS catid
	FROM transactions AS trn
	LEFT JOIN (
		SELECT 
			 categorymap.catid		AS catid
			,categorymap.userid 	AS userid
		FROM categorymap
		WHERE (categorymap.catid = 1001)
	) AS cty ON (cty.userid = trn.userid)
	WHERE (id = p_transaction_id)
	INTO v_buyer
		,v_seller
		,v_sku
		,v_datetime
		,v_catId;
		
	/*
	Map the nodes to tree.
	Add a new node to the seller.
	---------------------------- */
	SET @loggedOnUser = 0;
	SET @rowsAffected = 0;
	SET @operations = 'ADD';
	SET @newKey = 0;
	SET @memberId = v_buyer;
	SET @parentId = v_seller;
	SET @position = 0;
	SET @treeId = 1;
	SET @level = '';
	CALL sp_save_cm_nodes(
		 @loggedOnUser 	-- IN p_user_login INT(11)
		,@rowsAffected 	-- OUT p_rows_affected INT(11)
		,@operations 	-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
		,@newKey 		-- INOUT p_node_id INT(11)
		,@memberId 		-- IN p_member_id INT(11)
		,@parentId 		-- IN p_parent_id INT(11)
		,@position 		-- OUT p_position INT(11)
		,@treeId 		-- IN p_tree_id INT(11)
		,@level 		-- IN p_level TEXT
	);
	
	/*
	FREE MEMBER MODE.
	Do not continue if the transaction is for the free member
	because free member does not included in passup.
	Just save the commission orders.
	--------------------------------------------------------- */
	IF (sp_isFreeMember(v_buyer) = 'TRUE') THEN
		
		SET @userLogin = p_logged_on_user;
		SET @rowsAffected = 0;
		SET @mode = 'ADD';
		SET @new_key = 0;
		SET @sellerId = v_seller;
		SET @passupSponsorId = v_seller;
		SET @soldToId = v_buyer;
		SET @shoppingCartId = p_transaction_id;
		SET @salesCount = sp_getSalesCount(v_seller, v_sku);
		SET @commissionType = RETAIL;
		SET @commissionPercentage = .50;
		SET @notIncluded = 0;
		CALL sp_save_cm_commission_orders(
			 @userLogin 			-- IN p_user_login INT(11)
			,@rowsAffected 			-- OUT p_rows_affected INT(11)
			,@mode 					-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
			,@new_key 				-- INOUT p_id INT(11)
			,@sellerId 				-- IN p_seller_id INT(11)
			,@passupSponsorId 		-- IN p_passup_sponsor_id INT(11)
			,@soldToId 				-- IN p_sold_to_id INT(11)
			,@shoppingCartId 		-- IN p_shopping_cart_id INT(11)
			,@salesCount 			-- IN p_sales_count INT(11)
			,@commissionType 		-- IN p_commission_type INT(11)
			,@commissionPercentage 	-- IN p_commission_percentage FLOAT(9,3)
			,@notIncluded 			-- IN p_not_included INT(11)
		);
		
		SET @loggedOnUser = p_logged_on_user;
		SET @rowsAffected = 0;
		SET @operations = 'ADD';
		SET @new_key = 0;
		SET @sellerId = v_seller;
		SET @passupSponsorId = v_seller;
		SET @soldToId = v_buyer;
		SET @shoppingCartId = p_transaction_id;
		SET @salesCount = sp_getSalesCount(v_seller, v_sku);
		SET @commissionType = RETAIL_POOL;
		SET @commissionPercentage = 1;
		SET @notIncluded = NULL;
		CALL sp_save_cm_commission_orders(
			 @loggedOnUser 			-- IN p_user_login INT(11)
			,@rowsAffected 			-- OUT p_rows_affected INT(11)
			,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
			,@new_key 				-- INOUT p_id INT(11)
			,@sellerId 				-- IN p_seller_id INT(11)
			,@passupSponsorId 		-- IN p_passup_sponsor_id INT(11)
			,@soldToId 				-- IN p_sold_to_id INT(11)
			,@shoppingCartId 		-- IN p_shopping_cart_id INT(11)
			,@salesCount 			-- IN p_sales_count INT(11)
			,@commissionType 		-- IN p_commission_type INT(11)
			,@commissionPercentage 	-- IN p_commission_percentage FLOAT(9,3)
			,@notIncluded 			-- IN p_not_included INT(11)
		);
		
		/* 
		For checkmatch qualifications. 
		If an affiliate can sell 3 item to free member in a month this affiliate will be qualified 
		for the checkmatch commission for one month.
		And also a qualified affiliate that sell to a free member receive another 1 month qualification. */
		IF (sp_isCheckmatchQualified(v_seller) = 'FALSE') THEN
			
			SET v_firstDayOfTheMonth = DATE_FORMAT(DATE_ADD(v_datetime, INTERVAL -1 MONTH), '%Y-%m-%d');
			SET v_salesCount =	sp_getSalesCountPerMonth(v_seller, v_sku, v_firstDayOfTheMonth, v_datetime) + 1;
			IF (v_salesCount >= 2) THEN
				
				SET @userLogin = 0;
				SET @rows_affected = 0;
				SET @operations = 'ADD';
				SET @new_key = 0;
				SET @userId = v_seller;
				SET @productId = v_sku;
				SET @isQualified = 'Yes';
				CALL sp_save_cm_checkmatch(
					 @userLogin 	-- IN p_user_login INT(11)
					,@rows_affected -- OUT p_rows_affected INT(11)
					,@operations 	-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 		-- INOUT p_id INT(11)
					,@userId 		-- IN p_user_id INT(11)
					,@productId 	-- IN p_product_id INT(11)
					,@isQualified 	-- IN p_is_qualified ENUM('No','Yes')
				);
				
				SET @userLogin = 0;
				SET @rowsAffected = 0;
				SET @operations = 'ADD';
				SET @checkmatch_id = @new_key;
				SET @dateFrom = v_datetime;
				SET @dateTo = DATE_FORMAT(DATE_ADD(v_datetime, INTERVAL 1 MONTH), '%Y-%m-%d');
				SET @trasactionId = p_transaction_id;
				SET @enabled = 'Yes';
				CALL sp_save_cm_checkmatch_details(
					 @userLogin 	-- IN p_user_login INT(11)
					,@rowsAffected 	-- OUT p_rows_affected INT(11)
					,@operations 	-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 		-- INOUT p_id INT(11)
					,@checkmatch_id -- IN p_checkmatch_id INT(11)
					,@dateFrom 		-- IN p_date_from DATE
					,@dateTo 		-- IN p_date_to DATE
					,@trasactionId 	-- IN p_transaction_id INT(11)
					,@enabled		-- IN p_disabled ENUM('Yes', 'No')
				);
			END IF; /* IF (v_salesCount >= 2) THEN */
		ELSE 
			
			SELECT
				 cm.id 				AS checkmatch_id
				,MAX(cmd.date_to) 	AS date_to 
			FROM cm_checkmatch 		AS cm
			INNER JOIN cm_checkmatch_details AS cmd ON (cmd.checkmatch_id = cm.id)
			WHERE (cm.user_id = v_seller)
			GROUP BY cm.id
			INTO @checkmatch_id, @dateTo;
			
			SET @userLogin = 0;
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @dateFrom = @dateTo;
			SET @dateTo = DATE_FORMAT(DATE_ADD(@dateTo, INTERVAL 1 MONTH), '%Y-%m-%d');
			SET @new_key = 0;
			SET @transactionId = p_transaction_id;
			SET @enabled = 'Yes';
			CALL sp_save_cm_checkmatch_details(
				 @userLogin 		-- IN p_user_login INT(11)
				,@rowsAffected 		-- OUT p_rows_affected INT(11)
				,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@new_key 			-- INOUT p_id INT(11)
				,@checkmatch_id 	-- IN p_checkmatch_id INT(11)
				,@dateFrom 			-- IN p_date_from DATE
				,@dateTo 			-- IN p_date_to DATE
				,@transactionId 	-- IN p_transaction_id INT(11)
				,@enabled 			-- IN p_enabled ENUM('Yes', 'No')
			);
		END IF; /* IF (sp_isCheckmatchQualified(v_seller) = 'FALSE') THEN */
		
		CALL sp_setTransIsProcessed(p_transaction_id);
		CALL sp_set_flag('CALCULATE_PASSUP_ISBUSY', 'FALSE');		
		
		LEAVE PROC;
	END IF; /* IF (sp_isFreeMember(v_buyer) = 'TRUE') THEN */
	
	/*
	AFFILIATE MODE.
	Do the following below if the transaction is for affiliate. */
	
	/* 
	Below is for checkmatch qualification.
	For not qualified affiliate selling 3 items in a month he/she will be qualified for the 
	checkmatch commission for one month.
	For qualified affiliate that sell to a free member receive another 1 month qualification. 
	Affected tables: 
		1. cm_checkmatch.
		2. cm_checkmatch_details. */
	IF (sp_isCheckmatchQualified(v_seller) = 'FALSE') THEN
		
		SET v_firstDayOfTheMonth = DATE_FORMAT(DATE_ADD(v_datetime, INTERVAL -1 MONTH), '%Y-%m-%d');
		SET v_salesCount =	sp_getSalesCountPerMonth(v_seller, v_sku, v_firstDayOfTheMonth, v_datetime) + 1;
		IF (v_salesCount >= 2) THEN
			
			SET @userLogin = 0;
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @newKey = 0;
			SET @userId = v_seller;
			SET @productId = v_sku;
			SET @isQualified = 'Yes';
			CALL sp_save_cm_checkmatch(
				 @userLogin 	-- IN p_user_login INT(11)
				,@rowsAffected 	-- OUT p_rows_affected INT(11)
				,@operations 	-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 		-- INOUT p_id INT(11)
				,@userId 		-- IN p_user_id INT(11)
				,@productId 	-- IN p_product_id INT(11)
				,@isQualified 	-- IN p_is_qualified ENUM('No','Yes')
			);
			
			SET @userLogin = 0;
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @checkmatch_id = @newKey;
			SET @dateFrom = v_datetime;
			SET @dateTo = DATE_FORMAT(DATE_ADD(v_datetime, INTERVAL 1 MONTH), '%Y-%m-%d');
			SET @transactionId = p_transaction_id;
			SET @enabled = 'Yes';
			CALL sp_save_cm_checkmatch_details(
				 @userLogin 		-- IN p_user_login INT(11)
				,@rowsAffected 		-- OUT p_rows_affected INT(11)
				,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 			-- INOUT p_id INT(11)
				,@checkmatch_id 	-- IN p_checkmatch_id INT(11)
				,@dateFrom 			-- IN p_date_from DATE
				,@dateTo 			-- IN p_date_to DATE
				,@transactionId 	-- IN p_transaction_id INT(11)
				,@enabled			-- IN p_disabled ENUM('Yes', 'No')
			);
		END IF; /* IF (v_salesCount >= 2) THEN */
	END IF; /* IF (sp_isCheckmatchQualified(v_seller) = 'FALSE') THEN */
	
	SET v_level = sp_getNodeLevel(v_buyer, v_seller);
	IF (v_level <= 1) THEN
		
		SET @sales_count = sp_getSalesCount(v_seller, v_sku);
		SET v_passupPhase = 'NONE';
	ELSE
		
		/* What phase if the sales */
		SET @sales_count = sp_getSalesCount(v_seller, v_sku);
		SET v_passupPhase = sp_getPassupPhase(@sales_count + 1);
	END IF;
	
	SET @loggedOnUser = p_logged_on_user;
	SET @rowsAffected = 0;
	SET @operations = 'ADD';
	SET @newKey = 0;
	SET @sellerId = sp_GLOBAL('BARRY');
	SET @passupSponsorId = sp_GLOBAL('BARRY');
	SET @soldTo = v_buyer;
	SET @shoppingCartId = p_transaction_id;
	SET @salesCount = @sales_count;
	SET @commissionType = BARRY_COMM;
	SET @commissionPercentage = 1; /* 100% */
	SET @notIncluded = 0;
	CALL sp_save_cm_commission_orders(
		 @loggedOnUser 			-- IN p_user_login INT(11)
		,@rowsAffected 			-- OUT p_rows_affected INT(11)
		,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
		,@newKey 				-- INOUT p_id INT(11)
		,@sellerId 				-- IN p_seller_id INT(11)
		,@passupSponsorId 		-- IN p_passup_sponsor_id INT(11)
		,@soldTo 				-- IN p_sold_to_id INT(11)
		,@shoppingCartId 		-- IN p_shopping_cart_id INT(11)
		,@salesCount 			-- IN p_sales_count INT(11)
		,@commissionType 		-- IN p_commission_type INT(11)
		,@commissionPercentage 	-- IN p_commission_percentage FLOAT(9,3)
		,@notIncluded 			-- IN p_not_included INT(11)
	);
	
	IF (v_passupPhase = 'PHASE_1') THEN
		
		SET @loggedOnUser = p_logged_on_user;
		SET @mode = 'BUYER';
		SET @buyer = v_buyer;
		SET @sku = v_sku;
		SET @dateStamp = v_datetime;
		CALL sp_inrementSalesCount(
			 @loggedOnUser 	-- IN p_logged_on_user INT(11)
			,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
			,@buyer 		-- IN p_user_id INT(11)
			,@sku 			-- IN p_product_id INT(11)
			,@dateStamp 	-- IN p_datestamp VARCHAR(30)
		);
		
		SET @loggedOnUser = p_logged_on_user;
		SET @mode = 'SELLER';
		SET @seller = v_seller;
		SET @sku = v_sku;
		SET @dateStamp = v_datetime;
		CALL sp_inrementSalesCount(
			 @loggedOnUser	-- IN p_logged_on_user INT(11)
			,@mode 			-- SELLER mode.
			,@seller 		-- Qualified passup sponsor.
			,@sku 			-- product_id (sku).
			,@dateStamp 	-- The date of the transaction.
		);
		
		SET v_passupQualified = sp_getQualified(sp_getAbove(v_seller), v_sku, 'PHASE_1');
		IF (v_passupQualified > 0) THEN
			
			SET @loggedOnUser = 0;
			SET @mode = 'PASSUP-SPONSOR';
			SET @userId = v_passupQualified;
			SET @productId = v_sku;
			SET @dateStamp = v_datetime;
			CALL sp_inrementSalesCount(
				 @loggedOnUser 	-- IN p_logged_on_user INT(11)
				,@mode 			-- PASSUP-SPONSOR Mode.
				,@userId 		-- Qualified passup sponsor.
				,@productId 	-- product_id (sku)
				,@dateStamp 	-- The date of the transaction.
			);
			
			SET @loggedOnUser = p_logged_on_user;
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @userId = v_buyer;
			SET @productId = v_sku;
			SET @productSponsorId = v_passupQualified;
			SET @orderId = p_transaction_id;
			CALL sp_save_cm_product_sponsors(
				 @loggedOnUser 		-- IN p_user_login INT(11)
				,@rowsAffected 		-- OUT p_rows_affected INT(11)
				,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@new_key 			-- INOUT p_id INT(11)
				,@userId 			-- IN p_user_id INT(11)
				,@productId			-- IN p_product_id INT(11)
				,@productSponsorId 	-- IN p_product_sponsor_id INT(11)
				,@orderId 			-- IN p_order_id INT(11)
			);
			
			SET @userLogin = 0;
			SET @rows_affected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @user_id = v_buyer;
			SET @productId = v_sku;
			SET @checkMatchSponsorId = v_seller;
			SET @order_id = p_transaction_id;
			CALL sp_save_cm_checkmatch_sponsor(
					 @userLogin 			-- IN p_user_login INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 				-- INOUT p_id INT(11)
					,@user_id 				-- IN p_user_id INT(11)
					,@productId 			-- IN p_product_id INT(11)
					,@checkMatchSponsorId 	-- IN p_checkmatch_sponsor_id INT(11)
					,@order_id				-- IN p_order_id INT(11)
				);		
			
			/* 
			Get the sales count, the current right after the increment above. 
			this is recorded on commission orders. */
			SET v_salesCount = sp_getSalesCount(v_passupQualified, v_sku);
			
			SET @loggedOnUser = p_logged_on_user;
			SET @rowsAffected = 0;
			SET @operations = 'ADD';
			SET @newKey = 0;
			SET @sellerId = v_passupQualified;
			SET @passupSponsorId = v_passupQualified;
			SET @soldTo = v_buyer;
			SET @shoppingCartId = p_transaction_id;
			SET @salesCount = v_salesCount;
			SET @commissionType = CODED_COMM;
			SET @commissionPercentage = 1;
			SET @notIncluded = 0;
			CALL sp_save_cm_commission_orders(
				 @loggedOnUser 			-- IN p_user_login INT(11)
				,@rowsAffected 			-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@newKey 				-- INOUT p_id INT(11)
				,@sellerId 				-- IN p_seller_id INT(11)
				,@passupSponsorId 		-- IN p_passup_sponsor_id INT(11)
				,@soldTo 				-- IN p_sold_to_id INT(11)
				,@shoppingCartId 		-- IN p_shopping_cart_id INT(11)
				,@salesCount 			-- IN p_sales_count INT(11)
				,@commissionType 		-- IN p_commission_type INT(11)
				,@commissionPercentage 	-- IN p_commission_percentage FLOAT(9,3)
				,@notIncluded 			-- IN p_not_included INT(11)
			);
			
			SET @checkmatchSponsorId = sp_getCheckmatchSponsor(v_passupQualified, v_sku);
			IF (@checkmatchSponsorId > 0) AND 
			   (sp_isCheckmatchQualified(@checkmatchSponsorId) = 'TRUE') AND
			   (sp_isCheckmatchQualifiedByDate(@checkmatchSponsorId, v_datetime) = 'TRUE') THEN
				
				SET @loggedOnUser = 0;
				SET @rowsAffected = 0;
				SET @operations = 'ADD';
				SET @newKey = 0;
				SET @seller = @checkmatchSponsorId;
				SET @passupSponsorId = @checkmatchSponsorId;
				SET @soldTo = v_buyer;
				SET @shoppingCartId = p_transaction_id;
				SET @salesCount = v_salesCount;
				SET @commissionType = CHECKMATCH_COMM;
				SET @commissionPercentage = 1;
				SET @notIncluded = 0;
				CALL sp_save_cm_commission_orders(
					 @loggedOnUser 			-- IN p_user_login INT(11)
					,@rowsAffected 			-- OUT p_rows_affected INT(11)
					,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@newKey 				-- INOUT p_id INT(11)
					,@seller 				-- IN p_seller_id INT(11)
					,@passupSponsorId 		-- IN p_passup_sponsor_id INT(11)
					,@soldTo 				-- IN p_sold_to_id INT(11)
					,@shoppingCartId 		-- IN p_shopping_cart_id INT(11)
					,@salesCount 			-- IN p_sales_count INT(11)
					,@commissionType 		-- CHECK MATCH COMMISSION.
					,@commissionPercentage  -- IN p_commission_percentage FLOAT(9,3)
					,@notIncluded 			-- IN p_not_included INT(11)
				);
			END IF;
		END IF; -- (@qualified_id > 0)
	ELSEIF (v_passupPhase = 'PHASE_2') THEN

		/* 
		We increment sales count of the buyer.
		The initial sales count is -1 thus upon increment a buyer receive a 0 sales count.
		But if a buyer did not buy a product the sales count is -1 on the sales count table.*/
		SET @loggedOnUser = p_logged_on_user;
		SET @mode = 'BUYER';
		SET @userId = v_buyer;
		SET @productId = v_sku;
		SET @dateStamp = v_datetime;
		CALL sp_inrementSalesCount(
			 @loggedOnUser 	-- IN p_logged_on_user INT(11)
			,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
			,@userId 		-- IN p_user_id INT(11)
			,@productId 	-- IN p_product_id INT(11)
			,@dateStamp 	-- IN p_datestamp VARCHAR(30)
		);
		
		/*
		The seller affiliate are on the phase 2 passup.
		We go above by one from the seller affiliate and check if it is 
		qualified to receive phase 2 passup. */
		SET v_passupQualified = sp_getQualified(sp_getAbove(v_seller), v_sku, 'PHASE_2');
		IF (v_passupQualified > 0) THEN
			
			SET @loggedOnUser = p_logged_on_user;
			SET @mode = 'SELLER';
			SET @userId = v_seller;
			SET @sku = v_sku;
			SET @dateStamp = v_datetime;
			CALL sp_inrementSalesCount(
				 @loggedOnUser 	-- IN p_logged_on_user INT(11)
				,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
				,@userId 		-- IN p_user_id INT(11)
				,@sku 			-- IN p_product_id INT(11)
				,@dateStamp 	-- IN p_datestamp VARCHAR(30)
			);			
			
			/*
			The sponsor above the seller affiliate is qualified to receive the phase 2 passup.
			We will increase the sales count of that affiliate. */
			SET @loggedOnUser = p_logged_on_user;
			SET @mode = 'PASSUP-SPONSOR';
			SET @userId = v_passupQualified;
			SET @sku = v_sku;
			SET @dateStamp = v_datetime;
			CALL sp_inrementSalesCount(
				 @loggedOnUser 	-- IN p_logged_on_user INT(11)
				,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
				,@userId 		-- IN p_user_id INT(11)
				,@sku 			-- IN p_product_id INT(11)
				,@dateStamp 	-- IN p_datestamp VARCHAR(30)
			);
			
			SET @userLogin = 0;
			SET @rows_affected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @userId = v_buyer;
			SET @productId = v_sku;
			SET @productSponsorId = v_passupQualified;
			SET @orderId = p_transaction_id;
			CALL sp_save_cm_product_sponsors(
					 @userLogin 			-- IN p_user_login INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 				-- INOUT p_id INT(11)
					,@userId 				-- IN p_user_id INT(11)
					,@productId 			-- IN p_product_id INT(11)
					,@productSponsorId 		-- IN p_product_sponsor_id INT(11)
					,@orderId 				-- IN p_order_id INT(11)
				);
			
			SET @userLogin = 0;
			SET @rows_affected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @user_id = v_buyer;
			SET @productId = v_sku;
			SET @checkMatchSponsorId = v_seller;
			SET @order_id = p_transaction_id;
			CALL sp_save_cm_checkmatch_sponsor(
					 @userLogin 			-- IN p_user_login INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 				-- INOUT p_id INT(11)
					,@user_id 				-- IN p_user_id INT(11)
					,@productId 			-- IN p_product_id INT(11)
					,@checkMatchSponsorId 	-- IN p_checkmatch_sponsor_id INT(11)
					,@order_id				-- IN p_order_id INT(11)
				);
			
			SET v_salesCount = sp_getSalesCount(v_passupQualified, v_sku);
			CALL sp_save_cm_commission_orders(
				 p_logged_on_user 		-- IN p_user_login INT(11)
				,@p_rows_affected 		-- OUT p_rows_affected INT(11)
				,'ADD' 					-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_id 					-- INOUT p_id INT(11)
				,v_passupQualified 	-- IN p_seller_id INT(11)
				,v_passupQualified 	-- IN p_passup_sponsor_id INT(11)
				,v_buyer 				-- IN p_sold_to_id INT(11)
				,p_transaction_id 		-- IN p_shopping_cart_id INT(11)
				,v_salesCount 			-- IN p_sales_count INT(11)
				,CODED_COMM 			-- IN p_commission_type INT(11)
				,NULL 					-- IN p_commission_percentage FLOAT(9,3)
				,NULL 					-- IN p_not_included INT(11)
			);
			
			SET @checkmatchSponsor = sp_getCheckmatchSponsor(v_passupQualified, v_sku);
			IF (@checkmatchSponsor > 0) AND 
			   (sp_isCheckmatchQualified(@checkmatchSponsor) = 'TRUE') AND
               (sp_isCheckmatchQualifiedByDate(@checkmatchSponsor, v_datetime) = 'TRUE') THEN
				
				SET @operations = 'ADD';
				CALL sp_save_cm_commission_orders(
					 p_logged_on_user 	-- IN p_user_login INT(11)
					,@p_rows_affected 	-- OUT p_rows_affected INT(11)
					,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@p_id 				-- INOUT p_id INT(11)
					,@checkmatchSponsor -- IN p_seller_id INT(11)
					,@checkmatchSponsor -- IN p_passup_sponsor_id INT(11)
					,v_buyer 			-- IN p_sold_to_id INT(11)
					,p_transaction_id 	-- IN p_shopping_cart_id INT(11)
					,v_salesCount 		-- IN p_sales_count INT(11)
					,CHECKMATCH_COMM 	-- CHECK MATCH COMMISSION.
					,1 					-- IN p_commission_percentage FLOAT(9,3)
					,0 					-- IN p_not_included INT(11)
				);
			END IF;
		ELSE -- (v_passupQualified > 0) FALSE
			
			/*
			The seller affiliate is on phase 2 passup and the sponsor above the seller
			affiliate is not qualified to receive phase 2 passup.
			So we will gonna check the seller affiliate if he/she is qualified to receive as
			well if not we will put the phase 2 passup to master account. */
			SET v_passupQualified = sp_getQualified(v_seller, v_sku, 'PHASE_2');
			IF (v_passupQualified > 0) THEN
				
				SET @loggedOnUser = p_logged_on_user;
				SET @mode = 'SELLER';
				SET @userId = v_seller;
				SET @sku = v_sku;
				SET @dateStamp = v_datetime;
				CALL sp_inrementSalesCount(
					 @loggedOnUser 	-- IN p_logged_on_user INT(11)
					,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
					,@userId 		-- IN p_user_id INT(11)
					,@sku 			-- IN p_product_id INT(11)
					,@dateStamp 	-- IN p_datestamp VARCHAR(30)
				);

				SET @userLogin = 0;
				SET @rowsAffected = 0;
				SET @operations = 'ADD';
				SET @new_key = 0;
				SET @userId = v_buyer;
				SET @productId = v_sku;
				SET @productSponsorId = v_seller;
				SET @orderId = p_transaction_id;
				CALL sp_save_cm_product_sponsors(
					 @userLogin 		-- IN p_user_login INT(11)
					,@rowsAffected 		-- OUT p_rows_affected INT(11)
					,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 			-- INOUT p_id INT(11)
					,@userId 			-- IN p_user_id INT(11)
					,@productId 		-- IN p_product_id INT(11)
					,@productSponsorId 	-- IN p_product_sponsor_id INT(11)
					,@orderId 			-- IN p_order_id INT(11)
				);
				
				SET @userLogin = 0;
				SET @rows_affected = 0;
				SET @operations = 'ADD';
				SET @new_key = 0;
				SET @user_id = v_buyer;
				SET @productId = v_sku;
				SET @checkMatchSponsorId = v_seller;
				SET @order_id = p_transaction_id;
				CALL sp_save_cm_checkmatch_sponsor(
					 @userLogin 			-- IN p_user_login INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 				-- INOUT p_id INT(11)
					,@user_id 				-- IN p_user_id INT(11)
					,@productId 			-- IN p_product_id INT(11)
					,@checkMatchSponsorId 	-- IN p_checkmatch_sponsor_id INT(11)
					,@order_id				-- IN p_order_id INT(11)
				);
				
				SET v_salesCount = sp_getSalesCount(v_seller, v_sku);
				CALL sp_save_cm_commission_orders(
					 p_logged_on_user 	-- IN p_user_login INT(11)
					,@p_rows_affected 	-- OUT p_rows_affected INT(11)
					,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@p_id 				-- INOUT p_id INT(11)
					,v_seller 			-- IN p_seller_id INT(11)
					,v_seller 			-- IN p_passup_sponsor_id INT(11)
					,v_buyer 			-- IN p_sold_to_id INT(11)
					,p_transaction_id 	-- IN p_shopping_cart_id INT(11)
					,v_salesCount 		-- IN p_sales_count INT(11)
					,CODED_COMM 		-- IN p_commission_type INT(11)
					,1 					-- IN p_commission_percentage FLOAT(9,3)
					,0 					-- IN p_not_included INT(11)
				);
				
				SET v_productSponsor = sp_getCheckmatchSponsor(v_seller, v_sku);
				IF (v_productSponsor > 0) AND 
				   (sp_isCheckmatchQualified(v_productSponsor) = 'TRUE') AND 
				   (sp_isCheckmatchQualifiedByDate(v_productSponsor, v_datetime) = 'TRUE') THEN
					
					CALL sp_save_cm_commission_orders(
						 p_logged_on_user 	-- IN p_user_login INT(11)
						,@p_rows_affected 	-- OUT p_rows_affected INT(11)
						,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
						,@p_id 				-- INOUT p_id INT(11)
						,v_productSponsor 	-- IN p_seller_id INT(11)
						,v_productSponsor 	-- IN p_passup_sponsor_id INT(11)
						,v_buyer 			-- IN p_sold_to_id INT(11)
						,p_transaction_id 	-- IN p_shopping_cart_id INT(11)
						,v_salesCount 		-- IN p_sales_count INT(11)
						,CHECKMATCH_COMM 	-- CHECK MATCH COMMISSION.
						,1 					-- IN p_commission_percentage FLOAT(9,3)
						,0 					-- IN p_not_included INT(11)
					);
				END IF;
			ELSE
				
				/* We increase the sales count of the seller first. */
				SET @loggedOnUser = p_logged_on_user;
				SET @mode = 'SELLER';
				SET @userId = v_seller;
				SET @sku = v_sku;
				SET @dateStamp = v_datetime;
				CALL sp_inrementSalesCount(
					 @loggedOnUser 	-- IN p_logged_on_user INT(11)
					,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
					,@userId 		-- IN p_user_id INT(11)
					,@sku 			-- IN p_product_id INT(11)
					,@dateStamp 	-- IN p_datestamp VARCHAR(30)
				);
				
				/*
				Both of the seller affiliate and parent sponsor affiliate is not
				qualified to receive phase 2 passup so we will place the sales count to 
				the master account which is the code is 3. */
				SET @loggedOnUser = p_logged_on_user;
				SET @mode = 'PASSUP-SPONSOR';
				SET @userId = NAXUM_ADMIN;
				SET @sku = v_sku;
				SET @dateStamp = v_datetime;
				CALL sp_inrementSalesCount(
					 @loggedOnUser 	-- IN p_logged_on_user INT(11)
					,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
					,@userId 		-- IN p_user_id INT(11)
					,@sku 			-- IN p_product_id INT(11)
					,@dateStamp 	-- IN p_datestamp VARCHAR(30)
				);
				
				SET @loggedOnUser = 0;
				SET @rowsAffected = 0;
				SET @operations = 'ADD';
				SET @new_key = 0;
				SET @userId = v_buyer;
				SET @productId = v_sku;
				SET @productSponsorId = NAXUM_ADMIN;
				SET @orderId = p_transaction_id;
				CALL sp_save_cm_product_sponsors(
					 @loggedOnUser 		-- IN p_user_login INT(11)
					,@rowsAffected 		-- OUT p_rows_affected INT(11)
					,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 			-- INOUT p_id INT(11)
					,@userId 			-- IN p_user_id INT(11)
					,@productId 		-- IN p_product_id INT(11)
					,@productSponsorId 	-- IN p_product_sponsor_id INT(11)
					,@orderId 			-- IN p_order_id INT(11)
				);
				
				SET @userLogin = 0;
				SET @rows_affected = 0;
				SET @operations = 'ADD';
				SET @new_key = 0;
				SET @user_id = v_buyer;
				SET @productId = v_sku;
				SET @checkMatchSponsorId = v_seller;
				SET @order_id = p_transaction_id;
				CALL sp_save_cm_checkmatch_sponsor(
					 @userLogin 			-- IN p_user_login INT(11)
					,@rows_affected 		-- OUT p_rows_affected INT(11)
					,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 				-- INOUT p_id INT(11)
					,@user_id 				-- IN p_user_id INT(11)
					,@productId 			-- IN p_product_id INT(11)
					,@checkMatchSponsorId 	-- IN p_checkmatch_sponsor_id INT(11)
					,@order_id				-- IN p_order_id INT(11)
				);				
				
				SET v_salesCount = sp_getSalesCount(NAXUM_ADMIN, v_sku);
				CALL sp_save_cm_commission_orders(
					 p_logged_on_user 	-- IN p_user_login INT(11)
					,@p_rows_affected 	-- OUT p_rows_affected INT(11)
					,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@p_id 				-- INOUT p_id INT(11)
					,NAXUM_ADMIN 		-- IN p_seller_id INT(11)
					,NAXUM_ADMIN 		-- IN p_passup_sponsor_id INT(11)
					,v_buyer 			-- IN p_sold_to_id INT(11)
					,p_transaction_id 	-- IN p_shopping_cart_id INT(11)
					,v_salesCount 		-- IN p_sales_count INT(11)
					,CODED_COMM 		-- IN p_commission_type INT(11)
					,1 					-- IN p_commission_percentage FLOAT(9,3)
					,0 					-- IN p_not_included INT(11)
				);
				
				SET @loggedOnUser = p_logged_on_user;
				SET @rowsAffected = 0;
				SET @operations = 'ADD';
				SET @new_key = 0;
				SET @sellerId = NAXUM_ADMIN;
				SET @passupSponsorId = NAXUM_ADMIN;
				SET @soldTo = v_buyer;
				SET @shoppingCartId = p_transaction_id;
				SET @salesCount = v_salesCount;
				SET @commissionType = CHECKMATCH_COMM;
				CALL sp_save_cm_commission_orders(
					 @loggedOnUser 		-- IN p_user_login INT(11)
					,@rowsAffected 		-- OUT p_rows_affected INT(11)
					,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@p_id 				-- INOUT p_id INT(11)
					,@sellerId 			-- IN p_seller_id INT(11)
					,@passupSponsorId 	-- IN p_passup_sponsor_id INT(11)
					,@soldTo 			-- IN p_sold_to_id INT(11)
					,@shoppingCartId 	-- IN p_shopping_cart_id INT(11)
					,@salesCount 		-- IN p_sales_count INT(11)
					,@commissionType 	-- CHECK MATCH COMMISSION.
					,1 					-- IN p_commission_percentage FLOAT(9,3)
					,0 					-- IN p_not_included INT(11)
				);
			END IF; -- (v_passupQualified > 0)
			
		END IF; -- (v_passupQualified > 0)
		
	ELSEIF (v_passupPhase = 'NONE') THEN
		
		IF (@sales_count < 0) THEN
			
			SET @new_key = 0;
			SET @rows_affected = 0;
			SET v_productSponsor = sp_getNonNegativeSalesCount(sp_getAbove(v_seller), v_sku);
			IF NOT (v_productSponsor = sp_GLOBAL('ADMIN')) THEN -- 10 is admin.
				SET @phase = sp_get_passup_phase(sp_getSalesCount(v_productSponsor, v_sku));
				IF NOT (@phase = 'NONE') THEN
					SET v_productSponsor = sp_getQualified(v_productSponsor, v_sku, @phase);
				END IF;
			END IF;
			
			SET @loggedOnUser = p_logged_on_user;
			SET @mode = 'BUYER';
			SET @userId = v_buyer;
			SET @productId = v_sku;
			SET @dateStamp = v_datetime;
			CALL sp_inrementSalesCount(
				 @loggedOnUser	-- 
				,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
				,@userId 		-- IN p_user_id INT(11)
				,@productId 	-- IN p_product_id INT(11)
				,@dateStamp 	-- IN p_datestamp VARCHAR(30)
			);
			
			SET @loggedOnUser = p_logged_on_user;
			SET @mode = 'SELLER';
			SET @userId = v_seller;
			SET @productId = v_sku;
			SET @dateStamp = v_datetime;
			CALL sp_inrementSalesCount(
				 @loggedOnUser	-- 
				,@mode 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
				,@userId 		-- IN p_user_id INT(11)
				,@productId 	-- IN p_product_id INT(11)
				,@dateStamp 	-- IN p_datestamp VARCHAR(30)
			);
			
			SET @loggedOnUser = 0;
			SET @rows_affected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @userId = v_buyer;
			SET @productId = v_sku;
			SET @productSponsorId = v_productSponsor;
			SET @orderId = p_transaction_id;
			CALL sp_save_cm_product_sponsors(
				 @loggedOnUser 			-- IN p_user_login INT(11)
				,@rows_affected 		-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@new_key 				-- INOUT p_id INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@productId 			-- IN p_product_id INT(11)
				,@productSponsorId		-- IN p_product_sponsor_id INT(11)
				,@orderId 				-- IN p_order_id INT(11)
			);
			
			SET @userLogin = 0;
			SET @rows_affected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @userId = v_buyer;
			SET @productId = v_sku;
			SET @checkmatchSponsorId = v_productSponsor;
			SET @order_id = p_transaction_id;
			CALL sp_save_cm_checkmatch_sponsor(
				 @userLogin 			-- IN p_user_login INT(11)
				,@rows_affected 		-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@new_key 				-- INOUT p_id INT(11)
				,@userId 				-- IN p_user_id INT(11)
				,@productId 			-- IN p_product_id INT(11)
				,@checkmatchSponsorId 	-- IN p_checkmatch_sponsor_id INT(11)
				,@order_id				-- IN p_order_id INT(11)
			);
			
			SET v_salesCount = sp_getSalesCount(v_productSponsor, v_sku);
			CALL sp_save_cm_commission_orders(
				 p_logged_on_user 		-- IN p_user_login INT(11)
				,@p_rows_affected 		-- OUT p_rows_affected INT(11)
				,'ADD' 					-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_id 					-- INOUT p_id INT(11)
				,v_productSponsor 		-- IN p_seller_id INT(11)
				,v_productSponsor 		-- IN p_passup_sponsor_id INT(11)
				,v_buyer 				-- IN p_sold_to_id INT(11)
				,p_transaction_id 		-- IN p_shopping_cart_id INT(11)
				,v_salesCount 			-- IN p_sales_count INT(11)
				,CODED_COMM 			-- CHECK MATCH COMMISSION.
				,1 						-- IN p_commission_percentage FLOAT(9,3)
				,0 						-- IN p_not_included INT(11)
			);
			
			SET @checkmatchSponsorId = sp_getCheckmatchSponsor(v_productSponsor, v_sku);
			IF (@checkmatchSponsorId > 0) AND 
			   (sp_isCheckmatchQualified(@checkmatchSponsorId) = 'TRUE') AND 
			   (sp_isCheckmatchQualifiedByDate(@checkmatchSponsorId, v_datetime) = 'TRUE') THEN
				
				SET @loggedOnUser = 0;
				SET @rowsAffected = 0;
				SET @operations = 'ADD';
				SET @new_key = 0;
				SET @soldTo = v_buyer;
				SET @shoppingCartId = p_transaction_id;
				SET @salesCount = v_salesCount;
				SET @commissionPercentage = 1;
				SET @notIncluded = 0;
				CALL sp_save_cm_commission_orders(
					 @loggedOnUser 			-- IN p_user_login INT(11)
					,@rowsAffected 			-- OUT p_rows_affected INT(11)
					,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@new_key 				-- INOUT p_id INT(11)
					,@checkmatchSponsorId 	-- IN p_seller_id INT(11)
					,@checkmatchSponsorId 	-- IN p_passup_sponsor_id INT(11)
					,@soldTo 				-- IN p_sold_to_id INT(11)
					,@shoppingCartId 		-- IN p_shopping_cart_id INT(11)
					,@salesCount 			-- IN p_sales_count INT(11)
					,CHECKMATCH_COMM 		-- CHECK MATCH COMMISSION.
					,@commissionPercentage 	-- IN p_commission_percentage FLOAT(9,3)
					,@notIncluded 			-- IN p_not_included INT(11)
				);
			END IF;
			
		ELSE
			
			CALL sp_inrementSalesCount(
				 p_logged_on_user	-- 
				,'BUYER' 			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
				,v_buyer 			-- IN p_user_id INT(11)
				,v_sku 				-- IN p_product_id INT(11)
				,v_datetime 		-- IN p_datestamp VARCHAR(30)
			);
			
			CALL sp_inrementSalesCount(
				 p_logged_on_user	--
				,'SELLER'			-- IN p_mode ENUM('SELLER', 'BUYER', 'PASSUP-SPONSOR')
				,v_seller 			-- Qualified passup sponsor.
				,v_sku 				-- product_id (sku)
				,v_datetime 		-- IN p_datestamp VARCHAR(30)
			);
			
			SET @userLogin = 0;
			SET @rows_affected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @userId = v_buyer;
			SET @productId = v_sku;
			SET @productSponsorId = v_seller;
			SET @orderId = p_transaction_id;
			CALL sp_save_cm_product_sponsors(
				 @userLogin 		-- IN p_user_login INT(11)
				,@rows_affected 	-- OUT p_rows_affected INT(11)
				,@operations 		-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@new_key 			-- INOUT p_id INT(11)
				,@userId 			-- IN p_user_id INT(11)
				,@productId 		-- IN p_product_id INT(11)
				,@productSponsorId 	-- IN p_product_sponsor_id INT(11)
				,@orderId 			-- IN p_order_id INT(11)
			);
			
			SET @userLogin = 0;
			SET @rows_affected = 0;
			SET @operations = 'ADD';
			SET @new_key = 0;
			SET @user_id = v_buyer;
			SET @productId = v_sku;
			SET @checkMatchSponsorId = v_seller;
			SET @order_id = p_transaction_id;
			CALL sp_save_cm_checkmatch_sponsor(
				 @userLogin 			-- IN p_user_login INT(11)
				,@rows_affected 		-- OUT p_rows_affected INT(11)
				,@operations 			-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@new_key 				-- INOUT p_id INT(11)
				,@user_id 				-- IN p_user_id INT(11)
				,@productId 			-- IN p_product_id INT(11)
				,@checkMatchSponsorId 	-- IN p_checkmatch_sponsor_id INT(11)
				,@order_id				-- IN p_order_id INT(11)
			);
			
			SET v_salesCount = sp_getSalesCount(v_seller, v_sku);		
			CALL sp_save_cm_commission_orders(
				 p_logged_on_user 	-- IN p_user_login INT(11)
				,@p_rows_affected 	-- OUT p_rows_affected INT(11)
				,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
				,@p_id 				-- INOUT p_id INT(11)
				,v_seller 			-- IN p_seller_id INT(11)
				,v_seller 			-- IN p_passup_sponsor_id INT(11)
				,v_buyer 			-- IN p_sold_to_id INT(11)
				,p_transaction_id 	-- IN p_shopping_cart_id INT(11)
				,v_salesCount 		-- IN p_sales_count INT(11)
				,CODED_COMM 		-- CHECK MATCH COMMISSION.
				,1 					-- IN p_commission_percentage FLOAT(9,3)
				,0 					-- IN p_not_included INT(11)
			);
			
			SET @checkMatchSponsor = sp_getCheckmatchSponsor(v_seller, v_sku);
			IF (@checkMatchSponsor > 0) AND 
			   (sp_isCheckmatchQualified(@checkMatchSponsor) = 'TRUE') AND 
			   (sp_isCheckmatchQualifiedByDate(@checkMatchSponsor, v_datetime) = 'TRUE') THEN
				
				CALL sp_save_cm_commission_orders(
					 p_logged_on_user 	-- IN p_user_login INT(11)
					,@p_rows_affected 	-- OUT p_rows_affected INT(11)
					,'ADD' 				-- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
					,@p_id 				-- INOUT p_id INT(11)
					,@checkMatchSponsor -- IN p_seller_id INT(11)
					,@checkMatchSponsor -- IN p_passup_sponsor_id INT(11)
					,v_buyer 			-- IN p_sold_to_id INT(11)
					,p_transaction_id 	-- IN p_shopping_cart_id INT(11)
					,v_salesCount 		-- IN p_sales_count INT(11)
					,CHECKMATCH_COMM 	-- CHECK MATCH COMMISSION.
					,1 					-- IN p_commission_percentage FLOAT(9,3)
					,0 					-- IN p_not_included INT(11)
				);
			END IF;
			
		END IF; /* IF (v_salesCount < 0) THEN */
		
	END IF; -- IF (v_passupPhase = 'PHASE_1') THEN
	
	CALL sp_setTransIsProcessed(p_transaction_id);
	CALL sp_set_flag('CALCULATE_PASSUP_ISBUSY', 'FALSE');	
	
	END PROC;
END //
DELIMITER ;