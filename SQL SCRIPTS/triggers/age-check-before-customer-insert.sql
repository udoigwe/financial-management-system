DELIMITER $$

-- Trigger for INSERT
CREATE TRIGGER age_check_before_customer_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'Customer' AND TIMESTAMPDIFF(YEAR, NEW.dob, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Customers must be at least 18 years old';
    END IF;
END $$

DELIMITER;