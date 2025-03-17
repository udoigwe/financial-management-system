DELIMITER $$

CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    DECLARE user_role_value ENUM('Customer', 'Admin', 'Account Officer');

    -- Get the user's role from the users table
    SELECT role INTO user_role_value FROM users WHERE user_id = NEW.user_id;

    -- Only create an account if the user role is 'customer'
    IF user_role_value = 'Customer' THEN
        INSERT INTO account (user_id, account_type, balance, pin)
        VALUES (NEW.user_id, 'SAVINGS', 0.00, 1234);
    END IF;
END $$

DELIMITER ;