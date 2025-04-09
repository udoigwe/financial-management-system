DELIMITER $$

CREATE PROCEDURE GetMonthlyTransactionSummary(IN input_account_id INT)
BEGIN
    -- If input_account_id is NULL, get summary for all accounts
    IF input_account_id IS NULL THEN
        SELECT 
            DATE(created_at) AS payment_date,
            ROUND(SUM(CASE WHEN transaction_type = 'Credit' THEN amount ELSE 0 END), 2) AS total_credit,
            ROUND(SUM(CASE WHEN transaction_type = 'Debit' THEN amount ELSE 0 END), 2) AS total_debit
        FROM transactions
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
        GROUP BY DATE(created_at)
        ORDER BY payment_date;
    ELSE
        -- If input_account_id is provided, get summary for that specific account
        SELECT 
            DATE(created_at) AS payment_date,
            ROUND(SUM(CASE WHEN transaction_type = 'Credit' THEN amount ELSE 0 END), 2) AS total_credit,
            ROUND(SUM(CASE WHEN transaction_type = 'Debit' THEN amount ELSE 0 END), 2) AS total_debit
        FROM transactions
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
          AND account_id = input_account_id  -- Use the parameter name 'input_account_id' for comparison
        GROUP BY DATE(created_at)
        ORDER BY payment_date;
    END IF;
END $$

DELIMITER ;
