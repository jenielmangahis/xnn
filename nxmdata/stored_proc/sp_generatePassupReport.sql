DROP PROCEDURE IF EXISTS sp_generatePassupReport;
DELIMITER //
CREATE PROCEDURE sp_generatePassupReport(
	IN p_user_id INT(11)
)
BEGIN
	DECLARE v_done INT(11) DEFAULT FALSE;
	DECLARE v_id INT(11) DEFAULT 0;
	DECLARE v_name VARCHAR(256) DEFAULT '';
	DECLARE v_email VARCHAR(256) DEFAULT '';
	DECLARE v_phone VARCHAR(256) DEFAULT '';
	DECLARE v_order_id INT(11) DEFAULT 0;
	DECLARE v_product_sku INT(11) DEFAULT 0;
	DECLARE v_product VARCHAR(256) DEFAULT '';
	DECLARE fetchPassupReport CURSOR FOR
		SELECT
			 users.id 								AS `id`
			,CONCAT(users.fname, ' ', users.lname) 	AS `Name`
			,users.email							AS `Email`
			,users.dayphone							AS `Phone`
			,cm_product_sponsors.order_id			AS `OrderId`
			,shoppingcart_products.id 				AS `ProductSku`
			,shoppingcart_products.`name` 			AS `Product`
		FROM cm_product_sponsors
		INNER JOIN users 					ON (users.id = cm_product_sponsors.user_id)
		INNER JOIN shoppingcart_products 	ON (shoppingcart_products.id = cm_product_sponsors.product_id)
		WHERE (cm_product_sponsors.product_sponsor_id = p_user_id);
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;

	PROC: BEGIN

	CREATE TEMPORARY TABLE IF NOT EXISTS tbl_passup_report (
		 name VARCHAR(256) DEFAULT ''
		,email VARCHAR(256) DEFAULT ''
		,phone VARCHAR(256) DEFAULT ''
		,order_id INT(11) DEFAULT 0
		,product_sku INT(11) DEFAULT 0
		,product VARCHAR(256)
	);
	DELETE FROM tbl_passup_report;
	
	OPEN fetchPassupReport;
	loop_i: LOOP
		FETCH fetchPassupReport INTO 
			 v_id
			,v_name
			,v_email
			,v_phone
			,v_order_id
			,v_product_sku
			,v_product;
		IF (v_done) THEN
			SET v_done=FALSE;
			LEAVE loop_i;
		END IF;

		IF (sp_getAbove(v_id) <> p_user_id) THEN

			INSERT INTO tbl_passup_report(
				 name
				,email
				,phone
				,order_id
				,product_sku
				,product)
			VALUES(
				 v_name
				,COALESCE(v_email, '')
				,COALESCE(v_phone, '')
				,v_order_id
				,v_product_sku
				,v_product);
		END IF;
	END LOOP loop_i;
	CLOSE fetchPassupReport;

	SELECT
		 tbl_passup_report.name 		AS `Name`
		,tbl_passup_report.email		AS `Email`
		,tbl_passup_report.phone		AS `Phone`
		,tbl_passup_report.order_id		AS `OrderId`
		,tbl_passup_report.product_sku 	AS `ProductSku`
		,tbl_passup_report.product 		AS `Product`	
	FROM tbl_passup_report;

	END PROC;

END //
DELIMITER ;