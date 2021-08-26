DROP FUNCTION IF EXISTS sp_getAbove;
DELIMITER //
CREATE FUNCTION sp_getAbove(
	 p_user_id INT(11)
) RETURNS int(11)
BEGIN
	RETURN COALESCE(
			   (SELECT cm_nodes.parent_id FROM cm_nodes 
				WHERE (cm_nodes.member_id = p_user_id))
				, -1
			);
END //
DELIMITER ;