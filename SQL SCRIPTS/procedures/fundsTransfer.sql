DELIMITER //

CREATE PROCEDURE fundsTransfer(
    IN p_originating_account_id INT,
    IN p_destination_account_id INT,
    IN p_amount FLOAT,
    IN p_budget_category_id INT,
    IN p_source VARCHAR(20)
)
BEGIN
    DECLARE v_budget_limit FLOAT;
    DECLARE v_budget_start_time DATETIME;
    DECLARE v_budget_end_time DATETIME;
    DECLARE v_total_spent FLOAT DEFAULT 0;
    DECLARE v_originating_balance FLOAT;
    DECLARE v_destination_balance FLOAT;
    DECLARE v_transaction_budget_status VARCHAR(20);
    DECLARE v_transaction_fee FLOAT DEFAULT 0;
    DECLARE v_lock_start_time DATETIME;
    DECLARE v_lock_end_time DATETIME;
    DECLARE v_current_time DATETIME;
    DECLARE v_category_name VARCHAR(50);
    
    SET v_current_time = NOW();
    
    -- Get the budget limit details and category name
    SELECT budget_limit, budget_limit_start_time, budget_limit_end_time, category_name
    INTO v_budget_limit, v_budget_start_time, v_budget_end_time, v_category_name
    FROM budget_categories
    WHERE category_id = p_budget_category_id;
    
    -- Determine if category is 'Savings'
    IF v_category_name = 'Savings' THEN
        SET v_transaction_budget_status = 'Within Budget';
    -- If now is outside budget timeframe, consider it Within Budget
    ELSEIF v_current_time NOT BETWEEN v_budget_start_time AND v_budget_end_time THEN
        SET v_transaction_budget_status = 'Within Budget';
    ELSE
        -- Calculate total spent within budget time frame
        SELECT COALESCE(SUM(amount), 0)
        INTO v_total_spent
        FROM transactions
        WHERE account_id = p_originating_account_id
          AND budget_category_id = p_budget_category_id
          AND created_at BETWEEN v_budget_start_time AND v_budget_end_time;
        
        -- Determine if transaction exceeds budget
        IF (v_total_spent + p_amount) > v_budget_limit THEN
            SET v_transaction_budget_status = 'Exceeds Budget';
        ELSE
            SET v_transaction_budget_status = 'Within Budget';
        END IF;
    END IF;
    
    -- Check balance from the appropriate source
    IF p_source = 'Main Account' THEN
        SELECT balance INTO v_originating_balance FROM account WHERE account_id = p_originating_account_id;
    ELSEIF p_source = 'Safe Lock' THEN
        SELECT balance, lock_start_time, lock_end_time 
        INTO v_originating_balance, v_lock_start_time, v_lock_end_time
        FROM safe_lock WHERE account_id = p_originating_account_id;
        
        -- Apply transaction fee if outside lock period
        IF v_current_time BETWEEN v_lock_start_time AND v_lock_end_time THEN
            SET v_transaction_fee = p_amount * 0.05;
        END IF;
    END IF;
    
    -- Check if sufficient balance exists
    IF v_originating_balance < (p_amount + v_transaction_fee) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient funds';
    END IF;
    
    -- Deduct amount from originating account
    IF p_source = 'Main Account' THEN
        UPDATE account SET balance = balance - (p_amount + v_transaction_fee)
        WHERE account_id = p_originating_account_id;
    ELSEIF p_source = 'Safe Lock' THEN
        UPDATE safe_lock SET balance = balance - (p_amount + v_transaction_fee)
        WHERE account_id = p_originating_account_id;
    END IF;
    
    -- Credit the destination account
    IF v_category_name = 'Savings' THEN
        UPDATE safe_lock SET balance = balance + p_amount WHERE account_id = p_destination_account_id;
    ELSE
        UPDATE account SET balance = balance + p_amount WHERE account_id = p_destination_account_id;
    END IF;
    
    -- Get updated balances
    IF v_category_name = 'Savings' THEN
        SELECT balance INTO v_originating_balance FROM account WHERE account_id = p_originating_account_id;
        SELECT balance INTO v_destination_balance FROM safe_lock WHERE account_id = p_destination_account_id;
    ELSEIF p_source = 'Safe Lock' THEN
        SELECT balance INTO v_originating_balance FROM safe_lock WHERE account_id = p_originating_account_id;
        SELECT balance INTO v_destination_balance FROM account WHERE account_id = p_destination_account_id;
    ELSE
        SELECT balance INTO v_originating_balance FROM account WHERE account_id = p_originating_account_id;
        SELECT balance INTO v_destination_balance FROM account WHERE account_id = p_destination_account_id;
    END IF;
    
    -- Insert transaction record
    IF v_category_name = 'Savings' THEN
        INSERT INTO transactions (
            account_id, budget_category_id, transaction_type, amount, transaction_fee, balance_after_transaction,
            transaction_budget_status, transaction_description, transaction_source, transaction_destination
        ) VALUES (
            p_originating_account_id, p_budget_category_id, 'Debit', p_amount, v_transaction_fee, v_originating_balance,
            v_transaction_budget_status, 'Funds Transfer', p_source, 'Safe Lock'
        );
    ELSE
        INSERT INTO transactions (
            account_id, budget_category_id, transaction_type, amount, transaction_fee, balance_after_transaction,
            transaction_budget_status, transaction_description, transaction_source, transaction_destination
        ) VALUES (
            p_originating_account_id, p_budget_category_id, 'Debit', p_amount, v_transaction_fee, v_originating_balance,
            v_transaction_budget_status, 'Funds Transfer', p_source, 'Main Account'
        );
    END IF;

    IF v_category_name = 'Savings' THEN
        INSERT INTO transactions (
            account_id, sender_account_id, transaction_type, amount, transaction_fee, balance_after_transaction, transaction_description, transaction_source, transaction_destination
        ) VALUES (
            p_destination_account_id, p_originating_account_id, 'Credit', p_amount, 0, v_destination_balance, 'Funds Received', p_source, 'Safe Lock'
        );
    ELSE
        INSERT INTO transactions (
            account_id, sender_account_id, transaction_type, amount, transaction_fee, balance_after_transaction, transaction_description, transaction_source, transaction_destination
        ) VALUES (
            p_destination_account_id, p_originating_account_id, 'Credit', p_amount, 0, v_destination_balance, 'Funds Received', p_source, p_source
        );
    END IF;
    
    -- Return updated balances
    SELECT v_originating_balance AS originating_balance, v_destination_balance AS destination_balance;
END //

DELIMITER ;