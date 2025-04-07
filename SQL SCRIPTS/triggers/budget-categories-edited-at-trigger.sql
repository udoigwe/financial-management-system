DELIMITER $$

CREATE TRIGGER before_update_budget_categories
BEFORE UPDATE ON budget_categories
FOR EACH ROW
BEGIN
    SET NEW.edited_at = NOW();
END$$

DELIMITER ;