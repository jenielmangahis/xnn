DROP FUNCTION IF EXISTS sp_getNodeLevel;
DELIMITER //
CREATE FUNCTION sp_getNodeLevel(
	 p_member_id INT(11)
	,p_parent_id INT(11)
) RETURNS int(11)
BEGIN
	RETURN 
		COALESCE(
			(
			 SELECT 
				DISTINCT cn.level 
			 FROM cm_nodes AS cn
			 WHERE (cn.member_id = p_member_id) AND
			       (cn.parent_id = p_parent_id)
			)
			, 0
		);
END //
DELIMITER ;