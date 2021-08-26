DROP PROCEDURE IF EXISTS `sp_save_cm_nodes`;
DELIMITER //
CREATE PROCEDURE `sp_save_cm_nodes`(
	 IN 	p_user_login INT(11)
	,OUT 	p_rows_affected INT(11)
	,IN 	p_operation ENUM('ADD', 'EDIT', 'DELETE')
	,INOUT 	p_node_id INT(11)
	,IN 	p_member_id INT(11)
	,IN 	p_parent_id INT(11)
	,OUT 	p_position INT(11)
	,IN 	p_tree_id INT(11)
	,IN 	p_level INT(11)
)
BEGIN
	DECLARE v_path TEXT;
	
	IF (sp_is_column_exists('cm_nodes', 'path') = 'FALSE') THEN
		ALTER TABLE cm_nodes
		ADD COLUMN `path` TEXT AFTER heirarchy;
	END IF;

	IF (sp_is_column_exists('cm_nodes', 'level') = 'FALSE') THEN
		ALTER TABLE `cm_nodes`
		CHANGE COLUMN `heirarchy` `level`  int(11) NOT NULL AFTER `tree_id`;
	END IF;
	
	PROC: BEGIN
	
	IF (p_operation = 'ADD') THEN
		
		-- Get the position of the new node from left to right.
		-- With 1,2,3 .... ordering respectively.
		SET p_position = 
				COALESCE(
					 (SELECT COUNT(*) 
					  FROM cm_nodes AS cn 
					  WHERE (cn.parent_id = p_parent_id)
					  LIMIT 1
					 )
					,-1
				) + 1;

		-- Get the level of the new node from the root.
		-- If from the root node new node is only child then it should get
		-- 1 value.
		SET p_level = 
				COALESCE(
					 (SELECT 
						DISTINCT cn.level 
					  FROM cm_nodes AS cn 
					  WHERE (cn.member_id = p_parent_id)
					  LIMIT 1
					 )
					,0
				) + 1;

		-- Get the path of this new node going to the root.
		SET v_path = 
				COALESCE(
					 (
					  SELECT cm_nodes.path 
					  FROM cm_nodes 
					  WHERE (cm_nodes.member_id = p_parent_id) 
					  LIMIT 1
					 )
					,''
				);
		IF (v_path = '') THEN
			SET v_path = CONCAT(p_member_id, ',', p_parent_id);
		ELSE
			SET v_path = CONCAT(p_member_id, ',', v_path);
		END IF;
		
		INSERT INTO cm_nodes(
			 member_id
			,parent_id
			,position
			,tree_id
			,level
			,`path`)
		VALUES(
			 p_member_id
			,p_parent_id
			,p_position
			,p_tree_id
			,p_level
			,v_path);
		SET p_node_id = LAST_INSERT_ID();
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	ELSEIF (p_operation = 'EDIT') THEN
		
		UPDATE cm_nodes
		SET  member_id = p_member_id
			,parent_id = p_parent_id
			,position = p_position
			,tree_id = p_tree_id
			,level = p_level
		WHERE (node_id = p_node_id);
		SET p_rows_affected = COALESCE(ROW_COUNT(), 0);
	END IF;
	
	END PROC;
END //
DELIMITER ;