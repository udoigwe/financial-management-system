DELIMITER $$

CREATE PROCEDURE GenerateAccountStatementSummary(
    IN in_transaction_id INT,
    IN in_account_id INT,
    IN in_budget_category_id INT,
    IN in_transaction_type VARCHAR(10),
    IN in_transaction_budget_status VARCHAR(20),
    IN in_transaction_source VARCHAR(20),
    IN in_transaction_destination VARCHAR(20),
    IN in_from_created_at DATE,
    IN in_to_created_at DATE
)
BEGIN
    -- Variables
    DECLARE opening_main DECIMAL(18, 2) DEFAULT 0;
    DECLARE closing_main DECIMAL(18, 2) DEFAULT 0;
    DECLARE opening_safe DECIMAL(18, 2) DEFAULT 0;
    DECLARE closing_safe DECIMAL(18, 2) DEFAULT 0;

    DECLARE total_debit_main DECIMAL(18, 2) DEFAULT 0;
    DECLARE total_credit_main DECIMAL(18, 2) DEFAULT 0;
    DECLARE total_debit_safe DECIMAL(18, 2) DEFAULT 0;
    DECLARE total_credit_safe DECIMAL(18, 2) DEFAULT 0;

    DECLARE exceeds_count INT DEFAULT 0;
    DECLARE within_count INT DEFAULT 0;
    DECLARE spender_type VARCHAR(30);

    -- Temp table to store filtered transactions
    CREATE TEMPORARY TABLE temp_filtered AS
    SELECT *
    FROM transactions
    WHERE (in_transaction_id IS NULL OR transaction_id = in_transaction_id)
      AND (in_account_id IS NULL OR account_id = in_account_id)
      AND (in_budget_category_id IS NULL OR budget_category_id = in_budget_category_id)
      AND (in_transaction_type IS NULL OR transaction_type = in_transaction_type)
      AND (in_transaction_budget_status IS NULL OR transaction_budget_status = in_transaction_budget_status)
      AND (in_transaction_source IS NULL OR transaction_source = in_transaction_source)
      AND (in_transaction_destination IS NULL OR transaction_destination = in_transaction_destination)
      AND (in_from_created_at IS NULL OR DATE(created_at) >= in_from_created_at)
      AND (in_to_created_at IS NULL OR DATE(created_at) <= in_to_created_at)
    ORDER BY created_at ASC;

    -- Opening and closing balances for Main Account and Safe Lock
    -- Opening Main Account balance (first record from source or destination)
    SELECT COALESCE(balance_after_transaction, 0)
    INTO opening_main
    FROM temp_filtered
    WHERE transaction_source = 'Main Account' OR transaction_destination = 'Main Account'
    ORDER BY created_at ASC
    LIMIT 1;

    -- Closing Main Account balance (last record from source or destination)
    SELECT COALESCE(balance_after_transaction, 0)
    INTO closing_main
    FROM temp_filtered
    WHERE transaction_source = 'Main Account' OR transaction_destination = 'Main Account'
    ORDER BY created_at DESC
    LIMIT 1;

    -- Opening Safe Lock balance (first record from source or destination)
    SELECT COALESCE(balance_after_transaction, 0)
    INTO opening_safe
    FROM temp_filtered
    WHERE transaction_source = 'Safe Lock' OR transaction_destination = 'Safe Lock'
    ORDER BY created_at ASC
    LIMIT 1;

    -- Closing Safe Lock balance (last record from source or destination)
    SELECT COALESCE(balance_after_transaction, 0)
    INTO closing_safe
    FROM temp_filtered
    WHERE transaction_source = 'Safe Lock' OR transaction_destination = 'Safe Lock'
    ORDER BY created_at DESC
    LIMIT 1;

    -- Totals per source and destination
    -- For Main Account Debits (transaction_source = 'Main Account')
    SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'Debit' THEN amount ELSE 0 END), 0)
    INTO total_debit_main
    FROM temp_filtered
    WHERE transaction_source = 'Main Account';

    -- For Main Account Credits (transaction_destination = 'Main Account')
    SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'Credit' THEN amount ELSE 0 END), 0)
    INTO total_credit_main
    FROM temp_filtered
    WHERE transaction_destination = 'Main Account';

    -- For Safe Lock Debits (transaction_source = 'Safe Lock')
    SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'Debit' THEN amount ELSE 0 END), 0)
    INTO total_debit_safe
    FROM temp_filtered
    WHERE transaction_source = 'Safe Lock';

    -- For Safe Lock Credits (transaction_destination = 'Safe Lock')
    SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'Credit' THEN amount ELSE 0 END), 0)
    INTO total_credit_safe
    FROM temp_filtered
    WHERE transaction_destination = 'Safe Lock';

    -- Budget spender type
    SELECT 
        COALESCE(SUM(CASE WHEN transaction_budget_status = 'Exceeds Budget' THEN 1 ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN transaction_budget_status = 'Within Budget' THEN 1 ELSE 0 END), 0)
    INTO exceeds_count, within_count
    FROM temp_filtered;

    IF exceeds_count > within_count THEN
        SET spender_type = 'EXTRAVAGANT SPENDER';
    ELSE
        SET spender_type = 'METICULOUS SPENDER';
    END IF;

    -- Return the report as a single row
    SELECT 
        opening_main AS opening_main_account_balance,
        closing_main AS closing_main_account_balance,
        opening_safe AS opening_safe_lock_balance,
        closing_safe AS closing_safe_lock_balance,

        total_debit_main AS total_main_account_debit,
        total_credit_main AS total_main_account_credit,
        total_debit_safe AS total_safe_lock_debit,
        total_credit_safe AS total_safe_lock_credit,

        exceeds_count AS total_exceeds_budget,
        within_count AS total_within_budget,
        spender_type AS spender_category;

    -- Clean up
    DROP TEMPORARY TABLE IF EXISTS temp_filtered;
END $$

DELIMITER ;
