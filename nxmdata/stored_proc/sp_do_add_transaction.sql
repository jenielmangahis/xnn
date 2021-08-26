DROP PROCEDURE IF EXISTS sp_do_add_transaction;
DELIMITER //
CREATE PROCEDURE sp_do_add_transaction(
	 IN p_seller_fname VARCHAR(256) /* Ben - fname */
	,IN p_seller_lname VARCHAR(256) /* Ben - lname */
	,IN p_buyer_fname VARCHAR(256) 	/* Ken - fname */
	,IN p_buyer_lname VARCHAR(256) 	/* Ken - lname */
	,IN p_sku VARCHAR(256) 			/* 1003 */
	,IN p_datetime VARCHAR(256)		/* '2016-03-01 00:00:00' */
)
BEGIN
	/* ><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><---->< */
	/* ><                                        DECLARATION SECTION                                         >< */
	/* ><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><---->< */
	
	DECLARE v_user_id INT(11) DEFAULT 0;
	DECLARE v_result_userid INT(11) DEFAULT 0;
	DECLARE v_loggedin_user INT(11) DEFAULT 9999;
	DECLARE v_seller_sales_count INT(11) DEFAULT 0;
	DECLARE v_above_user_id INT(11) DEFAULT 0;
	DECLARE v_qualified_passup_sponsor INT(11) DEFAULT 0;
	DECLARE v_buyer_id INT(11);
	DECLARE v_buyer_fname VARCHAR(256);
	DECLARE v_buyer_lname VARCHAR(256);
	DECLARE v_seller_id INT(11);
	DECLARE v_seller_fname VARCHAR(256);
	DECLARE v_seller_lname VARCHAR(256);
	DECLARE v_passup_phase ENUM('NONE', 'PHASE_1', 'PHASE_2');
	DECLARE v_key INT(11);
	DECLARE v_trans_id INT(11);
	DECLARE v_trans_itemid INT(11);
	DECLARE v_out_trans_link INT(11);
	DECLARE v_trans_date VARCHAR(30);
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		SELECT 'An error occur on procedure sp_do_add_transaction.';
		SHOW ERRORS LIMIT 1;
	END;
	
	/* ><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><---->< */
	/* ><                                         BODY OF THE PROCEDURE                                      >< */
	/* ><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><---->< */
	
	CALL sp_do_append_row_by_key(
		v_loggedin_user
		,'users' -- p_table_name TEXT
		,'id' -- p_columns TEXT
		,'fname,lname' -- p_columns_where_clause TEXT
		,CONCAT('''', p_seller_fname, '''', ',', '''', p_seller_lname, '''') -- p_values_where_clause TEXT
		,@param -- OUT p_out_param TEXT
	);
	SET v_result_userid = sp_get_to_int(sp_get_element_at(@param, 0));
	IF (v_result_userid = 0) THEN
		
		SET v_user_id = 0;
		CALL sp_save_users(
			 v_loggedin_user -- IN p_user_login INT(11)
			,@p_rows_affected -- OUT p_rows_affected INT(11)
			,'ADD' -- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
			,v_user_id -- INOUT transaction_id INT(10)
			,null -- IN p_sponsorid INT(10)
			,0 -- IN p_leaderid INT(10)
			,'' -- IN p_site VARCHAR(24)
			,'' -- IN p_password VARCHAR(24)
			,'Yes' -- IN p_active ENUM('Yes', 'No', 'abused', 'Suspended')
			,p_seller_fname -- IN p_fname VARCHAR(50)
			,p_seller_lname -- IN p_lname VARCHAR(50)
			,'' -- IN p_address VARCHAR(60)
			,'' -- IN p_address2 VARCHAR(60)
			,'' -- IN p_city VARCHAR(40)
			,'' -- IN p_state VARCHAR(40)
			,'' -- IN p_zip VARCHAR(20)
			,'' -- IN p_country VARCHAR(50)
			,'' -- IN p_email VARCHAR(100)
			,'' -- IN p_dayphone VARCHAR(25)
			,'' -- IN p_evephone VARCHAR(15)
			,'' -- IN p_fax VARCHAR(15)
			,3 -- IN p_levelid TINYINT(3)
			,NOW() -- IN p_created DATETIME
			,NOW() -- IN p_modified TIMESTAMP
			,'' -- IN p_timezone VARCHAR(35)
			,'' -- IN p_besttime VARCHAR(20)
			,'' -- IN p_business VARCHAR(50)
			,'Yes' -- IN p_policy ENUM('No', 'Yes')
			,'' -- IN p_cellphone VARCHAR(15)
			,'personal' -- IN p_displayname ENUM('personal','business','both')
			,'Yes' -- IN p_newsletter ENUM('No','Yes')
			,0 -- IN p_locked TINYINT(3)
			,0 -- IN p_locktime INT(10)
			,'' -- IN p_memberid VARCHAR(50)
			,'' -- IN p_sponmemberid VARCHAR(50)
			,'Yes' -- IN p_blockemail ENUM('No','Yes')
			,'0' -- IN p_uploaded ENUM('0','1')
			,'' -- IN p_template CHAR(3)
			,'hard' -- IN p_bounced ENUM('soft', 'hard', 'blocked')
			,'' -- IN p_bouncereason VARCHAR(100)
			,0 -- IN p_importcatid INT(10)
			,0 -- IN p_holdid INT(11)
			,0 -- IN p_bulkleadbatchid INT(10)
			,'' -- IN p_ip VARCHAR(15)
			,'' -- IN p_optindatetime VARCHAR(35)
			,'Yes' -- IN p_displayphone ENUM('Yes', 'No')
			,'' -- IN p_referurl VARCHAR(150)
			,'' -- IN p_memberid2 VARCHAR(50)
			,NOW() -- IN p_formdate DATE
			,0 -- IN p_groutransaction_id INT(11)
			,0 -- IN p_teamid INT(11)
			,0 -- IN p_chatstatus INT(11)
			,0 -- IN p_change INT(11)
		);
		
		CALL sp_do_append_row_by_key(
			v_loggedin_user
			,'users' 			-- p_table_name TEXT
			,'id,fname,lname' 	-- p_columns TEXT
			,'id' 				-- p_columns_where_clause TEXT
			,v_user_id 			-- p_values_where_clause TEXT
			,@param 			-- OUT p_out_param TEXT
		);
		SET v_seller_id = COALESCE(NULLIF(sp_get_element_at(@param,0),''),0);
		SET	v_seller_fname = sp_get_element_at(@param, 1); 
		SET v_seller_lname = sp_get_element_at(@param, 2);
		
		IF (COALESCE((SELECT COUNT(*) FROM users), 0) = 1) THEN
			UPDATE users SET users.sponsorid = v_user_id WHERE (users.id = v_user_id);
		END IF;
	ELSE -- (v_result_userid = -1) FALSE
		
		CALL sp_do_append_row_by_key(
			v_loggedin_user
			,'users' 			-- p_table_name TEXT
			,'id,fname,lname' 	-- p_columns TEXT
			,'id' 				-- p_columns_where_clause TEXT
			,v_result_userid 	-- p_values_where_clause TEXT
			,@param 			-- OUT p_out_param TEXT
		);
		SET v_seller_id = COALESCE(NULLIF(sp_get_element_at(@param,0),''),0);
		SET	v_seller_fname = sp_get_element_at(@param, 1); 
		SET v_seller_lname = sp_get_element_at(@param, 2);
	END IF; -- (v_result_userid = -1)
	
	CALL sp_do_append_row_by_key(v_loggedin_user,'users', 'id', 'fname,lname', CONCAT('''', p_buyer_fname, '''', ',', '''', p_buyer_lname, ''''), @param);
	SET v_result_userid = COALESCE(NULLIF(sp_get_element_at(@param, 0),''), -1);
	IF (v_result_userid = -1) THEN
		
		SET v_user_id = 0;
		CALL sp_save_users(
			 v_loggedin_user -- IN p_user_login INT(11)
			,@p_rows_affected -- OUT p_rows_affected INT(11)
			,'ADD' -- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
			,v_user_id -- INOUT transaction_id INT(10)
			,v_seller_id -- IN p_sponsorid INT(10)
			,0 -- IN p_leaderid INT(10)
			,'' -- IN p_site VARCHAR(24)
			,'' -- IN p_password VARCHAR(24)
			,'Yes' -- IN p_active ENUM('Yes', 'No', 'abused', 'Suspended')
			,p_buyer_fname -- IN p_fname VARCHAR(50)
			,p_buyer_lname -- IN p_lname VARCHAR(50)
			,'' -- IN p_address VARCHAR(60)
			,'' -- IN p_address2 VARCHAR(60)
			,'' -- IN p_city VARCHAR(40)
			,'' -- IN p_state VARCHAR(40)
			,'' -- IN p_zip VARCHAR(20)
			,'' -- IN p_country VARCHAR(50)
			,'' -- IN p_email VARCHAR(100)
			,'' -- IN p_dayphone VARCHAR(25)
			,'' -- IN p_evephone VARCHAR(15)
			,'' -- IN p_fax VARCHAR(15)
			,0 -- IN p_levelid TINYINT(3)
			,NOW() -- IN p_created DATETIME
			,NOW() -- IN p_modified TIMESTAMP
			,'' -- IN p_timezone VARCHAR(35)
			,'' -- IN p_besttime VARCHAR(20)
			,'' -- IN p_business VARCHAR(50)
			,'Yes' -- IN p_policy ENUM('No', 'Yes')
			,'' -- IN p_cellphone VARCHAR(15)
			,'personal' -- IN p_displayname ENUM('personal','business','both')
			,'Yes' -- IN p_newsletter ENUM('No','Yes')
			,0 -- IN p_locked TINYINT(3)
			,0 -- IN p_locktime INT(10)
			,'' -- IN p_memberid VARCHAR(50)
			,'' -- IN p_sponmemberid VARCHAR(50)
			,'Yes' -- IN p_blockemail ENUM('No','Yes')
			,'0' -- IN p_uploaded ENUM('0','1')
			,'' -- IN p_template CHAR(3)
			,'hard' -- IN p_bounced ENUM('soft', 'hard', 'blocked')
			,'' -- IN p_bouncereason VARCHAR(100)
			,0 -- IN p_importcatid INT(10)
			,0 -- IN p_holdid INT(11)
			,0 -- IN p_bulkleadbatchid INT(10)
			,'' -- IN p_ip VARCHAR(15)
			,'' -- IN p_optindatetime VARCHAR(35)
			,'Yes' -- IN p_displayphone ENUM('Yes', 'No')
			,'' -- IN p_referurl VARCHAR(150)
			,'' -- IN p_memberid2 VARCHAR(50)
			,NOW() -- IN p_formdate DATE
			,0 -- IN p_groutransaction_id INT(11)
			,0 -- IN p_teamid INT(11)
			,0 -- IN p_chatstatus INT(11)
			,0 -- IN p_change INT(11)
		);
		
		CALL sp_do_append_row_by_key(v_loggedin_user,'users', 'id,fname,lname', 'id', v_user_id, @param);
		SET v_buyer_id = sp_get_to_int(sp_get_element_at(@param,0));
		SET	v_buyer_fname = sp_get_element_at(@param, 1); 
		SET v_buyer_lname = sp_get_element_at(@param, 2);
	ELSE -- (v_result_userid = -1) FALSE
		
		CALL sp_do_append_row_by_key(v_loggedin_user,'users', 'id,fname,lname', 'id', v_result_userid, @param);
		SET v_buyer_id = sp_get_to_int(sp_get_element_at(@param, 0));
		SET	v_buyer_fname = sp_get_element_at(@param, 1); 
		SET v_buyer_lname = sp_get_element_at(@param, 2);
	END IF; -- (v_result_userid = -1)
	
	SET v_key = 0;
	CALL sp_save_transactions(
		 v_loggedin_user -- IN p_user_login INT(11)							
		,@rows_affected -- OUT p_rows_affected INT(11)
		,'ADD' -- IN p_operation ENUM('ADD', 'EDIT', 'DELETE')
		,v_key -- INOUT transaction_id INT(10)
		,v_buyer_id -- IN p_userid INT(10)
		,v_seller_id -- IN p_sponsorid INT(10)
		,'' -- IN p_billfname VARCHAR(50)
		,'' -- IN p_billlname VARCHAR(50)
		,'' -- IN p_billaddress VARCHAR(60)
		,'' -- IN p_billcity VARCHAR(40)
		,'' -- IN p_billstate VARCHAR(40)
		,'' -- IN p_billzip VARCHAR(20)
		,'' -- IN p_billcountry VARCHAR(50)
		,'No' -- IN p_businessacct ENUM('No''Yes')
		,'' -- IN p_billbusiness VARCHAR(50)
		,'PAYPAL' -- IN p_billmethod ENUM('CC', 'ECHECK', 'WIRE', 'PAYPAL')
		,'' -- IN p_ccnumber VARCHAR(40)
		,''-- IN p_ccexp VARCHAR(4)
		,'' -- IN p_ccid VARCHAR(4)
		,'' -- IN p_bankname VARCHAR(50)
		,'Affiliate' -- IN p_acctype ENUM('CK', 'Customer', 'Affiliate', 'SA')
		,'' -- IN p_aba VARCHAR(9)
		,'' -- IN p_bankacctnum VARCHAR(50)
		,'' -- IN p_dlnum VARCHAR(40)
		,'' -- IN p_dlstate VARCHAR(20)
		,'' -- IN p_ssn VARCHAR(40)
		,'' -- IN p_invoice VARCHAR(100)
		,'' -- IN p_authcode VARCHAR(20)
		,'' -- IN p_refnum VARCHAR(42)
		,0.00 -- IN p_amount DECIMAL(12,2)
		,p_sku -- IN p_itemid INT(10)
		,'' -- IN p_description VARCHAR(200)
		,p_datetime -- IN p_transactiondate DATETIME
		,'' -- IN p_ip VARCHAR(15)
		,'' -- IN p_merchaccount VARCHAR(25)
		,'' -- IN p_credited VARCHAR(30)
		,'' -- IN p_status VARCHAR(30)
		,'' -- IN p_email VARCHAR(100)
		,'' -- IN p_error VARCHAR(255)
		,'' -- IN p_reconid VARCHAR(60)
		,'' -- IN p_requesttoken VARCHAR(255)
		,'' -- IN p_trackid VARCHAR(87)
		,0 -- IN p_billdate TINYINT(3)
		,'product' -- IN p_type ENUM('sub', 'campaign', 'lead', 'domain', 'product', 'textcr')
		,0 -- IN p_inclsetupfee TINYINT(3)
		,0 -- IN p_new TINYINT(3)
		,0 -- IN p_inclannualfee TINYINT(3)
		,0 -- IN p_is_processed INT(11)
		,0 -- IN p_is_wire_transfered INT(11)
		,'' -- IN p_wire_reference_number VARCHAR(20)
		,'' -- IN p_wire_date_paid VARCHAR(255)
	);
	
	/* ><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><---->< */
	/* ><                                          ALGORITHM FOR PASSUP.                                     >< */
	/* ><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><----><---->< */
	
	CALL sp_do_calculate_passup(v_loggedin_user, v_key);
	
END //
DELIMITER ;