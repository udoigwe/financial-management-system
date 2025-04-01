DELIMITER $$

CREATE TRIGGER before_update_notifications
BEFORE UPDATE ON notifications
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END$$

DELIMITER ;