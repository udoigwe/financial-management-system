DELIMITER $$

CREATE PROCEDURE checkTransactionBudget(
    IN p_originating_account INT,
    IN p_budget_category_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_source VARCHAR(20)  -- New parameter for source
)
BEGIN
    DECLARE v_budget_limit DECIMAL(10,2);
    DECLARE v_budget_start_time DATETIME;
    DECLARE v_budget_end_time DATETIME;
    DECLARE v_total_spent DECIMAL(10,2);
    DECLARE v_current_time DATETIME;
    DECLARE v_status VARCHAR(50);

    SET v_current_time = NOW();

    -- If source is 'Safe Lock', always return 'Within Budget'
    IF p_source = 'Safe Lock' THEN
        SET v_status = 'Within Budget';
    ELSE
        -- Get budget limit and time frame
        SELECT budget_limit, budget_limit_start_time, budget_limit_end_time 
        INTO v_budget_limit, v_budget_start_time, v_budget_end_time
        FROM budget_categories
        WHERE category_id = p_budget_category_id;

        -- Calculate total spent within budget period
        SELECT IFNULL(SUM(amount), 0) INTO v_total_spent
        FROM transactions
        WHERE account_it = p_originating_account
        AND budget_category_id = p_budget_category_id
        AND created_at BETWEEN v_budget_start_time AND v_budget_end_time;

        -- Determine budget status
        IF (v_total_spent + p_amount) > v_budget_limit THEN
            SET v_status = 'Exceeds Budget';
        ELSE
            SET v_status = 'Within Budget';
        END IF;
    END IF;

    -- Return the status
    SELECT v_status AS transaction_budget_status;
END $$

DELIMITER ;
