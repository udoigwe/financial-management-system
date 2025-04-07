DELIMITER //

CREATE PROCEDURE checkSafeLockPeriod(IN p_account_id INT)
BEGIN
    IF EXISTS (
        SELECT 1 
        FROM safe_lock 
        WHERE account_id = p_account_id
          AND NOW() BETWEEN lock_start_time AND lock_end_time
    ) THEN
        SELECT 'LOCK_ACTIVE' AS status;
    ELSE
        SELECT 'NO_LOCK' AS status;
    END IF;
END //

DELIMITER ;
