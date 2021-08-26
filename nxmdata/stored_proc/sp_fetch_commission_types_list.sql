DROP PROCEDURE IF EXISTS sp_fetch_commission_types_list;
DELIMITER //
CREATE PROCEDURE sp_fetch_commission_types_list(
)
BEGIN
	SELECT
		0									AS `id`
		,'All daily commission' 			AS `name`
	UNION
	SELECT
		 cpt.`commission_payout_type_id`	AS `id`
		,cpt.`name`							AS `name`
	FROM cm_commission_payout_types AS cpt
	WHERE (cpt.commission_payout_type_id IN (5,6,7,8));
END //
DELIMITER ;