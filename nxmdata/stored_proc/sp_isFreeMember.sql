DROP FUNCTION IF EXISTS sp_isFreeMember;
DELIMITER //
CREATE FUNCTION sp_isFreeMember(
	p_user_id INT(11)
) RETURNS ENUM('TRUE', 'FALSE')
BEGIN
	IF (p_user_id = sp_GLOBAL('ADMIN')) THEN
		
		RETURN 'FALSE';
	ELSE
		
		RETURN 
			COALESCE(
				(
				 SELECT
					CASE WHEN (categorymap.catid = 1000)
						THEN 'TRUE'
						ELSE 'FALSE'
					END AS `retval`
				 FROM users
				 LEFT JOIN categorymap ON (users.id = categorymap.userid)
				 WHERE
					(users.id = p_user_id) AND
					((categorymap.catid = 1001) OR (categorymap.catid = 1000))
				 LIMIT 1
				)
				,'FALSE'
			);
	END IF;
END //
DELIMITER ;