DELIMITER $$

CREATE TRIGGER create_account_after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    DECLARE new_account_id INT;

    -- Only process if the new user is a customer
    IF NEW.role = 'Customer' THEN
        -- Call the stored procedure to create an account and get the account_id
        CALL newAccount(NEW.user_id, new_account_id);

        -- Insert a record into the safe_lock table using the new account_id
        IF new_account_id IS NOT NULL THEN
            INSERT INTO safe_lock (account_id, balance, lock_start_time, lock_end_time)
            VALUES (new_account_id, 0.0, NOW(), NOW() + INTERVAL 30 MINUTE);
        END IF;
    END IF;
END $$

DELIMITER ;