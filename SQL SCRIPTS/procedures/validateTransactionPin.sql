DELIMITER $$

CREATE PROCEDURE validateTransactionPIN(
    IN p_user_id INT,
    IN p_entered_pin VARCHAR(255)
)
BEGIN
    DECLARE stored_pin INT;
    DECLARE user_exists INT DEFAULT 0;
    
    -- Check if user exists
    SELECT COUNT(*) INTO user_exists 
    FROM users 
    WHERE user_id = p_user_id;

    -- If user does not exist, return 'USER_NOT_FOUND'
    IF user_exists = 0 THEN
        SELECT 'USER_NOT_FOUND' AS result;
    ELSE
        -- Retrieve the stored PIN
        SELECT pin INTO stored_pin
        FROM account_view
        WHERE user_id = p_user_id;
        
        -- Validate PIN
        IF stored_pin = p_entered_pin THEN
            SELECT 'SUCCESS' AS result;
        ELSE
            SELECT 'INVALID_PIN' AS result;
        END IF;
    END IF;
END $$

DELIMITER ;
