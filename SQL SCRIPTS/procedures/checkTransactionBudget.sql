DELIMITER $$

CREATE PROCEDURE checkTransactionBudget(
    IN p_originating_account INT,
    IN p_budget_category_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_source VARCHAR(20)
)
BEGIN
    DECLARE v_budget_limit DECIMAL(10,2);
    DECLARE v_budget_start_time DATETIME;
    DECLARE v_budget_end_time DATETIME;
    DECLARE v_total_spent DECIMAL(10,2);
    DECLARE v_status VARCHAR(50);
    DECLARE v_current_time DATETIME;
    DECLARE v_category_name VARCHAR(100);

    SET v_current_time = NOW();

    -- Get budget details including category name
    SELECT 
        budget_limit, 
        budget_limit_start_time, 
        budget_limit_end_time,
        category_name
    INTO 
        v_budget_limit, 
        v_budget_start_time, 
        v_budget_end_time,
        v_category_name
    FROM budget_categories
    WHERE category_id = p_budget_category_id;

    -- If category is 'Savings', always within budget
    IF v_category_name = 'Savings' THEN
        SET v_status = 'Within Budget';
    ELSEIF v_current_time NOT BETWEEN v_budget_start_time AND v_budget_end_time THEN
        SET v_status = 'Within Budget';
    ELSE
        -- Get total spent within the timeframe
        SELECT IFNULL(SUM(amount), 0) INTO v_total_spent
        FROM transactions
        WHERE budget_category_id = p_budget_category_id
        AND created_at BETWEEN v_budget_start_time AND v_budget_end_time;

        -- Check against limit
        IF (v_total_spent + p_amount) > v_budget_limit THEN
            SET v_status = 'Exceeds Budget';
        ELSE
            SET v_status = 'Within Budget';
        END IF;
    END IF;

    -- Return result
    SELECT v_status AS transaction_budget_status;
END $$

DELIMITER ;
