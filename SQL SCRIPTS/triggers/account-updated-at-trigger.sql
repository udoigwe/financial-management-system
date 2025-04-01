DELIMITER $$

CREATE TRIGGER before_update_account
BEFORE UPDATE ON account
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END$$

DELIMITER ;