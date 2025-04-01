DELIMITER $$

CREATE TRIGGER before_update_safe_lock
BEFORE UPDATE ON safe_lock
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END$$

DELIMITER ;