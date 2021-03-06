DROP FUNCTION IF EXISTS sp_get_lastday_of_nextmonth;
DELIMITER //
CREATE FUNCTION sp_get_lastday_of_nextmonth() RETURNS DATE
BEGIN
	RETURN (SELECT LAST_DAY(DATE_ADD(NOW(), INTERVAL 1 MONTH)));
END //
DELIMITER ;