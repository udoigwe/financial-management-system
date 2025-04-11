DELIMITER $$

CREATE PROCEDURE newAccount(IN new_user_id INT, OUT new_account_id INT)
BEGIN
    DECLARE min_officer_id INT DEFAULT NULL;

    -- Find the account officer with the least number of assigned customers
    SELECT user_id 
    INTO min_officer_id
    FROM (
        SELECT u.user_id AS user_id, COUNT(a.user_id) AS customer_count
        FROM users u
        LEFT JOIN account a ON u.user_id = a.account_officer_id
        WHERE u.role = 'Account Officer'
        GROUP BY u.user_id
        ORDER BY customer_count ASC
        LIMIT 1
    ) AS selected_officer;

    -- If no account officer is found, do nothing
    IF min_officer_id IS NOT NULL THEN
        -- Insert new account record for the customer and assign the selected account officer
        INSERT INTO account (user_id, account_officer_id, account_type, balance, pin)
        VALUES (new_user_id, min_officer_id, 'SAVINGS', 1500.0, 1234);

        -- Retrieve the last inserted account_id
        SET new_account_id = LAST_INSERT_ID();

        -- create a new budget category for this account and call it SAVINGS
        INSERT INTO budget_categories (account_id, category_name, category_description, budget_limit, budget_limit_start_time, budget_limit_end_time, color_code)
        VALUES (new_account_id, 'Savings', 'Budget category associated with the SafeLock', 0, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 30 DAY), '%Y-%m-%d %H:%i:00'), '#FB23231F'); 
    END IF;
END $$

DELIMITER ;