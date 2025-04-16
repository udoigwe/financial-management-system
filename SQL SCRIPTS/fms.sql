-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2025 at 11:51 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fms`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `checkSafeLockPeriod` (IN `p_account_id` INT)   BEGIN
    IF EXISTS (
        SELECT 1 
        FROM safe_lock 
        WHERE account_id = p_account_id
          AND NOW() BETWEEN lock_start_time AND lock_end_time
    ) THEN
        SELECT 'LOCK_ACTIVE' AS status;
    ELSE
        SELECT 'NO_LOCK' AS status;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `checkTransactionBudget` (IN `p_originating_account` INT, IN `p_budget_category_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_source` VARCHAR(20))   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `fundsTransfer` (IN `p_originating_account_id` INT, IN `p_destination_account_id` INT, IN `p_amount` FLOAT, IN `p_budget_category_id` INT, IN `p_source` VARCHAR(20))   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateAccountStatementSummary` (IN `in_transaction_id` INT, IN `in_account_id` INT, IN `in_budget_category_id` INT, IN `in_transaction_type` VARCHAR(10), IN `in_transaction_budget_status` VARCHAR(20), IN `in_transaction_source` VARCHAR(20), IN `in_transaction_destination` VARCHAR(20), IN `in_from_created_at` DATE, IN `in_to_created_at` DATE)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetMonthlyTransactionSummary` (IN `input_account_id` INT)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `newAccount` (IN `new_user_id` INT, OUT `new_account_id` INT)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `storeNotification` (IN `p_user_id` INT, IN `p_title` VARCHAR(255), IN `p_message` TEXT)   BEGIN
    INSERT INTO notifications (user_id, title, message)
    VALUES (p_user_id, p_title, p_message);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `validateTransactionPIN` (IN `p_user_id` INT, IN `p_entered_pin` VARCHAR(255))   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `verifyOTP` (IN `p_account_id` INT, IN `p_otp` INT)   BEGIN
    DECLARE otp_count INT;
    DECLARE otp_expired INT;
    
    -- Check if the OTP exists
    SELECT COUNT(*) INTO otp_count 
    FROM otp
    WHERE account_id = p_account_id 
    AND otp = p_otp;
    
    IF otp_count = 0 THEN
        SELECT 'OTP_NOT_FOUND' AS status;
    ELSE
        -- Check if the OTP has expired
        SELECT COUNT(*) INTO otp_expired 
        FROM otp
        WHERE account_id = p_account_id 
        AND otp = p_otp 
        AND expires_at <= NOW();
        
        IF otp_expired > 0 THEN
            SELECT 'OTP_EXPIRED' AS status;
        ELSE
            -- OTP is valid, delete it and return success status
            DELETE FROM otp WHERE account_id = p_account_id;
            SELECT 'OTP_VALID' AS status;
        END IF;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `account_officer_id` int(11) DEFAULT NULL,
  `account_type` enum('Savings','Current','Fixed Deposit') NOT NULL DEFAULT 'Savings',
  `pin` int(6) NOT NULL,
  `balance` float NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_id`, `user_id`, `account_officer_id`, `account_type`, `pin`, `balance`, `created_at`, `updated_at`) VALUES
(1000000029, 31, 30, 'Savings', 1234, 1500, '2025-03-31 22:12:43', NULL),
(1000000032, 34, 28, 'Savings', 1234, 2422.82, '2025-04-01 05:47:17', '2025-04-13 21:22:02'),
(1000000033, 35, 23, 'Savings', 1234, 1014.18, '2025-04-01 21:48:19', '2025-04-13 21:58:24'),
(1000000034, 43, 24, 'Savings', 1234, 1400, '2025-04-11 20:42:53', '2025-04-16 19:49:19'),
(1000000035, 44, 26, 'Savings', 1234, 1468, '2025-04-11 21:26:35', '2025-04-11 21:42:23'),
(1000000036, 45, 40, 'Savings', 1234, 1500, '2025-04-13 20:48:27', '2025-04-13 23:05:34'),
(1000000037, 46, 27, 'Savings', 1234, 1500, '2025-04-13 21:05:26', NULL),
(1000000038, 47, 38, 'Savings', 1234, 747, '2025-04-13 21:08:44', '2025-04-13 21:54:26'),
(1000000039, 49, 39, 'Savings', 1234, 1500, '2025-04-16 20:04:59', NULL),
(1000000040, 50, 48, 'Savings', 1234, 1500, '2025-04-16 20:08:42', NULL),
(1000000041, 51, 23, 'Savings', 1234, 1500, '2025-04-16 20:12:32', NULL),
(1000000042, 52, 24, 'Savings', 1234, 1500, '2025-04-16 20:19:08', NULL),
(1000000043, 53, 26, 'Savings', 1234, 1500, '2025-04-16 21:49:21', NULL);

--
-- Triggers `account`
--
DELIMITER $$
CREATE TRIGGER `after_account_insert` AFTER INSERT ON `account` FOR EACH ROW BEGIN
    DECLARE new_card_number VARCHAR(16);
    DECLARE expiry DATE;

    -- Generate a random 16-digit card number
    SET new_card_number = CONCAT(
        FLOOR(RAND() * 9000) + 1000, -- 4 digits
        FLOOR(RAND() * 9000) + 1000, -- 4 digits
        FLOOR(RAND() * 9000) + 1000, -- 4 digits
        FLOOR(RAND() * 9000) + 1000  -- 4 digits
    );

    -- Set the expiry date (4 years from now)
    SET expiry = DATE_ADD(CURDATE(), INTERVAL 4 YEAR);

    -- Insert into the cards table
    INSERT INTO cards (account_id, card_number, expiry_date, card_type)
    VALUES (NEW.account_id, new_card_number, expiry, 'DEBIT');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_account` BEFORE UPDATE ON `account` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `account_view`
-- (See below for the actual view)
--
CREATE TABLE `account_view` (
`account_id` int(11)
,`user_id` int(11)
,`account_officer_id` int(11)
,`account_type` enum('Savings','Current','Fixed Deposit')
,`pin` int(6)
,`balance` float
,`created_at` timestamp
,`updated_at` timestamp
,`first_name` varchar(255)
,`last_name` varchar(255)
,`dob` date
,`email` varchar(255)
,`password` varchar(255)
,`gender` enum('Male','Female','Others')
,`identification` enum('Drivers License','SSN')
,`identification_number` varchar(255)
,`phone` varchar(255)
,`role` enum('Admin','Account Officer','Customer')
,`last_seen` timestamp
,`joined_at` timestamp
,`account_status` enum('Active','Inactive')
,`account_officer_first_name` varchar(255)
,`account_officer_last_name` varchar(255)
,`account_officer_phone` varchar(255)
,`account_officer_email` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `budget_categories`
--

CREATE TABLE `budget_categories` (
  `category_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_description` text NOT NULL,
  `budget_limit` float NOT NULL DEFAULT 0,
  `budget_limit_start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `budget_limit_end_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `color_code` varchar(255) NOT NULL,
  `budget_category_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `budget_categories`
--

INSERT INTO `budget_categories` (`category_id`, `account_id`, `category_name`, `category_description`, `budget_limit`, `budget_limit_start_time`, `budget_limit_end_time`, `color_code`, `budget_category_status`, `created_at`, `edited_at`) VALUES
(3, 1000000029, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-03-31 22:12:00', '2025-04-10 22:12:00', '#FB23231F', 'Active', '2025-03-31 22:12:43', '2025-04-16 20:53:06'),
(6, 1000000032, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-01 05:47:00', '2025-04-11 05:47:00', '#FB23231F', 'Active', '2025-04-01 05:47:17', '2025-04-16 20:53:21'),
(11, 1000000032, 'Entertainment/Subscriptions', 'Budget category to manage my entertainment lifestyles', 160, '2025-04-01 12:24:00', '2025-04-25 12:24:00', '#501b1b', 'Active', '2025-04-01 12:25:13', NULL),
(12, 1000000032, 'Transportation/Fuel', 'This is i', 145, '2025-04-01 18:29:00', '2025-04-26 15:29:00', '#b38080', 'Active', '2025-04-01 12:29:24', '2025-04-13 22:54:39'),
(13, 1000000032, 'Rent', 'sdsdsg', 212, '2025-04-01 12:30:00', '2025-04-16 12:30:00', '#71762e', 'Active', '2025-04-01 12:31:03', '2025-04-13 22:54:19'),
(14, 1000000032, 'Housing/Mortgage', 'aaddad', 1244, '2025-04-01 12:33:00', '2025-04-30 12:33:00', '#9f7ec9', 'Active', '2025-04-01 12:33:33', NULL),
(15, 1000000032, 'Miscellaneous', 'I want to manage my miscelllaneous spendings', 1212, '2025-04-01 12:36:00', '2025-04-30 12:36:00', '#a55f5f', 'Active', '2025-04-01 12:38:04', NULL),
(16, 1000000032, 'Investments', 'Monitoring my investment strategies', 323, '2025-04-11 13:13:00', '2025-04-25 13:13:00', '#9787ab', 'Active', '2025-04-01 13:13:30', NULL),
(17, 1000000032, 'Drinks', 'I want to save 230 this month on drinks', 230, '2025-04-03 13:14:00', '2025-04-27 13:14:00', '#473e3e', 'Active', '2025-04-01 13:14:46', NULL),
(18, 1000000033, 'Savings', 'Budget category associated with the SafeLock', 1, '2025-04-01 21:48:00', '2025-04-11 21:48:00', '#000000', 'Active', '2025-04-01 21:48:19', '2025-04-16 20:53:28'),
(19, 1000000033, 'Miscellaneous', 'This will guide me in my miscellaneous spendings', 140, '2025-04-01 00:59:00', '2025-04-23 02:00:00', '#55a595', 'Active', '2025-04-01 21:55:26', '2025-04-11 21:17:52'),
(20, 1000000033, 'Drinks', 'Budget on drinking', 140, '2025-04-01 21:56:00', '2025-04-17 21:56:00', '#48934a', 'Active', '2025-04-01 21:56:52', '2025-04-11 21:17:35'),
(21, 1000000034, 'Savings', 'Budget category associated with the SafeLock', 100, '2025-04-11 20:42:00', '2025-04-21 20:42:00', '#000000', 'Active', '2025-04-11 20:42:53', '2025-04-16 20:53:36'),
(22, 1000000035, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-11 21:26:00', '2025-04-30 21:26:00', '#000000', 'Active', '2025-04-11 21:26:35', '2025-04-11 21:37:31'),
(23, 1000000036, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-13 20:48:00', '2025-05-13 20:48:00', '#000000', 'Active', '2025-04-13 20:48:27', '2025-04-13 20:50:59'),
(24, 1000000037, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-13 21:05:00', '2025-05-13 21:05:00', '#FB23231F', 'Active', '2025-04-13 21:05:26', NULL),
(25, 1000000038, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-13 21:08:00', '2025-05-29 21:08:00', '#ab41c8', 'Active', '2025-04-13 21:08:44', '2025-04-13 21:13:30'),
(26, 1000000038, 'Rent', 'Budget for my rent', 500, '2025-04-11 21:14:00', '2025-04-30 21:14:00', '#79ecc9', 'Active', '2025-04-13 21:15:48', '2025-04-13 21:17:45'),
(27, 1000000034, 'Travel/Vacations', 'Travel ', 123, '2025-04-26 22:25:00', '2025-04-30 22:25:00', '#d0ecd0', 'Active', '2025-04-13 22:25:53', NULL),
(28, 1000000034, 'Housing/Mortgage', 'Housing', 305, '2025-04-03 22:27:00', '2025-04-27 22:27:00', '#700a0a', 'Active', '2025-04-13 22:27:49', NULL),
(29, 1000000034, 'Food/Groceries', 'Food', 230, '2025-04-03 22:28:00', '2025-04-27 22:28:00', '#a3981f', 'Active', '2025-04-13 22:28:22', NULL),
(30, 1000000039, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-16 20:04:00', '2025-05-16 20:04:00', '#FB23231F', 'Active', '2025-04-16 20:04:59', NULL),
(31, 1000000040, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-16 20:08:00', '2025-05-16 20:08:00', '#FB23231F', 'Active', '2025-04-16 20:08:42', NULL),
(32, 1000000041, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-16 20:12:00', '2025-05-16 20:12:00', '#FB23231F', 'Active', '2025-04-16 20:12:32', NULL),
(33, 1000000042, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-16 20:19:00', '2025-05-16 20:19:00', '#000000', 'Active', '2025-04-16 20:19:08', '2025-04-16 20:29:13'),
(34, 1000000042, 'Investments', 'Investment category', 121, '2025-04-01 20:30:00', '2025-04-26 20:30:00', '#1c3b63', 'Active', '2025-04-16 20:30:32', NULL),
(35, 1000000043, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-16 21:49:00', '2025-05-16 21:49:00', '#FB23231F', 'Active', '2025-04-16 21:49:21', NULL);

--
-- Triggers `budget_categories`
--
DELIMITER $$
CREATE TRIGGER `before_update_budget_categories` BEFORE UPDATE ON `budget_categories` FOR EACH ROW BEGIN
    SET NEW.edited_at = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_budget_category` BEFORE UPDATE ON `budget_categories` FOR EACH ROW BEGIN
    SET NEW.edited_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `budget_categories_view`
-- (See below for the actual view)
--
CREATE TABLE `budget_categories_view` (
`category_id` int(11)
,`account_id` int(11)
,`category_name` varchar(255)
,`category_description` text
,`budget_limit` float
,`budget_limit_start_time` timestamp
,`budget_limit_end_time` timestamp
,`color_code` varchar(255)
,`budget_category_status` enum('Active','Inactive')
,`created_at` timestamp
,`edited_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE `cards` (
  `card_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `card_number` varchar(255) NOT NULL,
  `expiry_date` date NOT NULL,
  `card_type` enum('CREDIT','DEBIT') NOT NULL,
  `issue_date` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `cards`
--

INSERT INTO `cards` (`card_id`, `account_id`, `card_number`, `expiry_date`, `card_type`, `issue_date`) VALUES
(30, 1000000029, '1609902532986417', '2029-03-31', 'DEBIT', '2025-03-31 22:12:43'),
(33, 1000000032, '8826857163765170', '2029-04-01', 'DEBIT', '2025-04-01 05:47:17'),
(34, 1000000033, '7339800880236091', '2029-04-01', 'DEBIT', '2025-04-01 21:48:19'),
(35, 1000000034, '6144783317332165', '2029-04-11', 'DEBIT', '2025-04-11 20:42:53'),
(36, 1000000035, '4698144911529412', '2029-04-11', 'DEBIT', '2025-04-11 21:26:35'),
(37, 1000000036, '1562105485842760', '2029-04-13', 'DEBIT', '2025-04-13 20:48:27'),
(38, 1000000037, '3716215776383717', '2029-04-13', 'DEBIT', '2025-04-13 21:05:26'),
(39, 1000000038, '1097477916061690', '2029-04-13', 'DEBIT', '2025-04-13 21:08:44'),
(40, 1000000039, '1761917936127526', '2029-04-16', 'DEBIT', '2025-04-16 20:04:59'),
(41, 1000000040, '8202900114005996', '2029-04-16', 'DEBIT', '2025-04-16 20:08:42'),
(42, 1000000041, '9174289639571099', '2029-04-16', 'DEBIT', '2025-04-16 20:12:32'),
(43, 1000000042, '7807326298892660', '2029-04-16', 'DEBIT', '2025-04-16 20:19:08'),
(44, 1000000043, '2546578823012144', '2029-04-16', 'DEBIT', '2025-04-16 21:49:21');

-- --------------------------------------------------------

--
-- Stand-in structure for view `cards_view`
-- (See below for the actual view)
--
CREATE TABLE `cards_view` (
`card_id` int(11)
,`account_id` int(11)
,`card_number` varchar(255)
,`expiry_date` date
,`card_type` enum('CREDIT','DEBIT')
,`issue_date` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_status` enum('Read','Unread') NOT NULL DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `notification_status`, `created_at`, `updated_at`) VALUES
(1, 31, 'Login Successful', 'Welcome onboard Uchechukwu Udo', 'Read', '2025-03-31 23:52:44', NULL),
(2, 31, 'Login Successful', 'Welcome onboard Uchechukwu Udo', 'Read', '2025-03-31 23:53:18', NULL),
(3, 31, 'Login Successful', 'Welcome onboard Uchechukwu Udo', 'Read', '2025-04-01 03:53:54', NULL),
(4, 34, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-01 05:47:22', NULL),
(5, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Unread', '2025-04-01 05:48:26', NULL),
(6, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Unread', '2025-04-01 09:05:24', NULL),
(7, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Read', '2025-04-01 11:56:19', NULL),
(8, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:04:48', NULL),
(9, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:25:13', NULL),
(10, 34, 'Budget Category Created', 'Budget Category created successfully', 'Read', '2025-04-01 12:29:24', NULL),
(11, 34, 'Budget Category Created', 'Budget Category created successfully', 'Read', '2025-04-01 12:31:03', NULL),
(12, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:33:33', NULL),
(13, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:38:04', NULL),
(14, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 13:13:30', NULL),
(15, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 13:14:46', NULL),
(16, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 14:35:56', NULL),
(17, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 14:36:53', NULL),
(18, 34, 'Support Email Sent', 'A support email has been sent toudoigweuchechukwu@gmail.com', 'Unread', '2025-04-01 16:14:14', NULL),
(19, 34, 'Support Email Sent', 'A support email has been sent toudoigweuchechukwu@gmail.com', 'Read', '2025-04-01 16:18:35', NULL),
(20, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:01:50', NULL),
(21, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:04:01', NULL),
(22, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:04:06', NULL),
(23, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:05:22', NULL),
(24, 34, 'Password Update', 'Password update was successful', 'Unread', '2025-04-01 21:00:24', NULL),
(25, 34, 'Pin Update', 'Transaction Pin update was successful', 'Unread', '2025-04-01 21:11:16', NULL),
(26, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:22:38', NULL),
(27, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:23:14', NULL),
(28, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:24:22', NULL),
(29, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Unread', '2025-04-01 21:32:53', NULL),
(30, 35, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-01 21:48:29', NULL),
(31, 35, 'Login Successful', 'Welcome onboard Tolu Ayo', 'Unread', '2025-04-01 21:50:18', NULL),
(32, 35, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 21:55:26', NULL),
(33, 35, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 21:56:52', NULL),
(34, 35, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:57:28', NULL),
(35, 35, 'Support Email Sent', 'A support email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-01 21:59:58', NULL),
(36, 35, 'Account Update', 'Account updated successfully', 'Read', '2025-04-01 23:00:42', NULL),
(37, 35, 'Password Update', 'Password update was successful', 'Unread', '2025-04-01 23:01:29', NULL),
(38, 35, 'Pin Update', 'Transaction Pin update was successful', 'Unread', '2025-04-01 23:01:59', NULL),
(39, 35, 'Login Successful', 'Welcome onboard Tolu Ayo', 'Unread', '2025-04-04 21:17:57', NULL),
(40, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Read', '2025-04-05 07:45:43', '2025-04-05 07:52:34'),
(41, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-05 07:47:27', NULL),
(42, 35, 'Debit Alert', 'Your account has been debited with $150', 'Read', '2025-04-05 07:49:20', '2025-04-05 07:52:27'),
(43, 34, 'Credit Alert', 'Your account has been credited with $150', 'Unread', '2025-04-05 07:49:20', NULL),
(44, 35, 'Debit Alert', 'Your account has been debited with $30', 'Unread', '2025-04-05 07:58:01', NULL),
(45, 34, 'Credit Alert', 'Your account has been credited with $30', 'Unread', '2025-04-05 07:58:01', NULL),
(46, 35, 'Debit Alert', 'Your account has been debited with $10', 'Unread', '2025-04-05 08:00:38', NULL),
(47, 34, 'Credit Alert', 'Your account has been credited with $10', 'Unread', '2025-04-05 08:00:38', NULL),
(48, 35, 'Debit Alert', 'Your account has been debited with $3.4', 'Unread', '2025-04-05 08:05:00', NULL),
(49, 34, 'Credit Alert', 'Your account has been credited with $3.4', 'Unread', '2025-04-05 08:05:00', NULL),
(50, 35, 'Debit Alert', 'Your account has been debited with $3.1', 'Unread', '2025-04-05 08:06:32', NULL),
(51, 34, 'Credit Alert', 'Your account has been credited with $3.1', 'Unread', '2025-04-05 08:06:32', NULL),
(52, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-05 09:37:48', NULL),
(53, 35, 'Debit Alert', 'Your account has been debited with $100', 'Unread', '2025-04-05 09:38:29', NULL),
(54, 35, 'Credit Alert', 'Your account has been credited with $100', 'Unread', '2025-04-05 09:38:29', NULL),
(55, 35, 'Debit Alert', 'Your account has been debited with $150.76', 'Unread', '2025-04-05 12:45:47', NULL),
(56, 34, 'Credit Alert', 'Your account has been credited with $150.76', 'Unread', '2025-04-05 12:45:47', NULL),
(57, 35, 'Debit Alert', 'Your account has been debited with $141.50', 'Unread', '2025-04-05 19:06:08', NULL),
(58, 34, 'Credit Alert', 'Your account has been credited with $141.50', 'Unread', '2025-04-05 19:06:08', NULL),
(59, 35, 'Debit Alert', 'Your account has been debited with $150', 'Unread', '2025-04-05 19:07:42', NULL),
(60, 34, 'Credit Alert', 'Your account has been credited with $150', 'Unread', '2025-04-05 19:07:42', NULL),
(61, 35, 'Debit Alert', 'Your account has been debited with $142.56', 'Unread', '2025-04-05 19:13:01', NULL),
(62, 34, 'Credit Alert', 'Your account has been credited with $142.56', 'Unread', '2025-04-05 19:13:01', NULL),
(63, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-05 19:48:35', NULL),
(64, 35, 'Debit Alert', 'Your account has been debited with $141.76', 'Unread', '2025-04-05 19:49:16', NULL),
(65, 34, 'Credit Alert', 'Your account has been credited with $141.76', 'Unread', '2025-04-05 19:49:16', NULL),
(66, 35, 'Debit Alert', 'Your account has been debited with $150', 'Unread', '2025-04-05 19:51:47', NULL),
(67, 35, 'Credit Alert', 'Your account has been credited with $150', 'Unread', '2025-04-05 19:51:47', NULL),
(68, 35, 'Debit Alert', 'Your account has been debited with $130', 'Unread', '2025-04-05 19:53:14', NULL),
(69, 35, 'Credit Alert', 'Your account has been credited with $130', 'Unread', '2025-04-05 19:53:14', NULL),
(70, 35, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-05 20:10:14', NULL),
(71, 35, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-05 20:10:41', NULL),
(72, 35, 'Debit Alert', 'Your account has been debited with $115', 'Unread', '2025-04-05 22:35:47', NULL),
(73, 35, 'Credit Alert', 'Your account has been credited with $115', 'Unread', '2025-04-05 22:35:47', NULL),
(74, 35, 'Debit Alert', 'Your account has been debited with $112', 'Unread', '2025-04-05 22:35:57', NULL),
(75, 35, 'Credit Alert', 'Your account has been credited with $112', 'Unread', '2025-04-05 22:35:57', NULL),
(76, 35, 'Debit Alert', 'Your account has been debited with $12', 'Unread', '2025-04-05 22:40:04', NULL),
(77, 35, 'Credit Alert', 'Your account has been credited with $12', 'Unread', '2025-04-05 22:40:04', NULL),
(78, 35, 'Debit Alert', 'Your account has been debited with $13', 'Unread', '2025-04-05 22:43:58', NULL),
(79, 35, 'Credit Alert', 'Your account has been credited with $13', 'Unread', '2025-04-05 22:43:58', NULL),
(80, 35, 'Debit Alert', 'Your account has been debited with $34', 'Unread', '2025-04-05 22:49:19', NULL),
(81, 34, 'Credit Alert', 'Your account has been credited with $34', 'Unread', '2025-04-05 22:49:19', NULL),
(82, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-05 22:51:26', NULL),
(83, 35, 'Debit Alert', 'Your account has been debited with $13', 'Unread', '2025-04-05 22:51:56', NULL),
(84, 34, 'Credit Alert', 'Your account has been credited with $13', 'Unread', '2025-04-05 22:51:56', NULL),
(85, 35, 'Debit Alert', 'Your account has been debited with $34', 'Unread', '2025-04-05 23:06:29', NULL),
(86, 34, 'Credit Alert', 'Your account has been credited with $34', 'Unread', '2025-04-05 23:06:29', NULL),
(87, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-05 23:07:34', NULL),
(88, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-05 23:10:19', NULL),
(89, 35, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-05 23:11:56', NULL),
(90, 35, 'Debit Alert', 'Your account has been debited with $234', 'Read', '2025-04-05 23:12:22', '2025-04-06 07:49:39'),
(91, 34, 'Credit Alert', 'Your account has been credited with $234', 'Unread', '2025-04-05 23:12:22', NULL),
(92, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-10 17:19:04', NULL),
(93, 38, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 19:25:52', NULL),
(94, 22, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 19:25:52', NULL),
(95, 39, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 19:58:01', NULL),
(96, 22, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 19:58:01', NULL),
(97, 40, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 20:06:08', NULL),
(98, 22, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 20:06:08', NULL),
(99, 42, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 20:16:57', NULL),
(100, 22, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-10 20:16:57', NULL),
(101, 38, 'Account Update', 'Your account was updated successfully', 'Unread', '2025-04-10 20:49:09', NULL),
(102, 22, 'Account Update', 'User updated successfully', 'Unread', '2025-04-10 20:49:09', NULL),
(103, 39, 'Account Update', 'Your account was updated successfully', 'Unread', '2025-04-10 20:49:39', NULL),
(104, 22, 'Account Update', 'User updated successfully', 'Unread', '2025-04-10 20:49:39', NULL),
(105, 40, 'Account Update', 'Your account was updated successfully', 'Unread', '2025-04-10 20:49:53', NULL),
(106, 22, 'Account Update', 'User updated successfully', 'Unread', '2025-04-10 20:49:53', NULL),
(107, 42, 'Account Update', 'Your account was updated successfully', 'Unread', '2025-04-10 20:53:41', NULL),
(108, 22, 'Account Update', 'User updated successfully', 'Unread', '2025-04-10 20:53:41', NULL),
(109, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-10 20:54:08', NULL),
(110, 35, 'Login Successful', 'Welcome onboard Tolu Ayo', 'Unread', '2025-04-10 22:03:48', NULL),
(111, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-10 22:10:16', NULL),
(112, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Read', '2025-04-10 22:10:50', '2025-04-10 22:10:53'),
(113, 40, 'Login Successful', 'Welcome onboard Melvine Heinz', 'Unread', '2025-04-10 22:34:24', NULL),
(114, 30, 'Login Successful', 'Welcome onboard Yaz Sullivan', 'Unread', '2025-04-10 22:51:39', NULL),
(115, 40, 'Login Successful', 'Welcome onboard Melvine Heinz', 'Unread', '2025-04-10 22:56:01', NULL),
(116, 40, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-10 23:20:28', NULL),
(117, 40, 'Login Successful', 'Welcome onboard Melvine Heinz', 'Unread', '2025-04-11 19:47:49', NULL),
(118, 30, 'Login Successful', 'Welcome onboard Yaz Sullivan', 'Unread', '2025-04-11 19:48:45', NULL),
(119, 23, 'Login Successful', 'Welcome onboard John Woo', 'Unread', '2025-04-11 19:51:08', NULL),
(120, 23, 'Login Successful', 'Welcome onboard John Woo', 'Unread', '2025-04-11 20:30:08', NULL),
(121, 23, 'Login Successful', 'Welcome onboard John Woo', 'Unread', '2025-04-11 20:30:23', NULL),
(122, 43, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-11 20:42:56', '2025-04-13 22:31:42'),
(123, 23, 'Login Successful', 'Welcome onboard John Woo', 'Unread', '2025-04-11 20:55:13', NULL),
(124, 43, 'Login Successful', 'Welcome onboard Laura Hills', 'Unread', '2025-04-11 21:06:20', NULL),
(125, 35, 'Login Successful', 'Welcome onboard Tolu Ayo', 'Unread', '2025-04-11 21:16:45', NULL),
(126, 35, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-11 21:17:35', NULL),
(127, 35, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-11 21:17:52', NULL),
(128, 44, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-11 21:26:37', NULL),
(129, 44, 'Login Successful', 'Welcome onboard Harission Ford', 'Unread', '2025-04-11 21:27:18', NULL),
(130, 44, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-11 21:35:33', NULL),
(131, 44, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-11 21:37:31', NULL),
(132, 44, 'Debit Alert', 'Your account has been debited with $32', 'Unread', '2025-04-11 21:42:32', NULL),
(133, 44, 'Credit Alert', 'Your account has been credited with $32', 'Unread', '2025-04-11 21:42:32', NULL),
(134, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-11 22:39:47', NULL),
(135, 30, 'Login Successful', 'Welcome onboard Yaz Sullivan', 'Unread', '2025-04-11 22:41:43', NULL),
(136, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-11 22:47:27', NULL),
(137, 22, 'Support Email Sent', 'An email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-11 23:15:07', '2025-04-11 23:15:12'),
(138, 44, 'Support Email Received', 'An email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-11 23:15:07', NULL),
(139, 45, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-13 20:48:30', NULL),
(140, 45, 'Budget Categories', 'Please endevor to setup budget categories to track spending', 'Unread', '2025-04-13 20:48:30', NULL),
(141, 45, 'Login Successful', 'Welcome onboard James Earl', 'Unread', '2025-04-13 20:49:02', NULL),
(142, 45, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 20:49:27', NULL),
(143, 45, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 20:50:59', NULL),
(144, 46, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-13 21:05:29', NULL),
(145, 46, 'Budget Categories', 'Please endevor to setup budget categories to track spending', 'Unread', '2025-04-13 21:05:29', NULL),
(146, 46, 'Login Successful', 'Welcome onboard Michael Jackson', 'Unread', '2025-04-13 21:06:10', NULL),
(147, 47, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-13 21:08:47', '2025-04-13 21:11:08'),
(148, 47, 'Budget Categories', 'Please endevor to setup budget categories to track spending', 'Read', '2025-04-13 21:08:47', '2025-04-13 21:11:22'),
(149, 47, 'Login Successful', 'Welcome onboard Helena Kai', 'Read', '2025-04-13 21:09:34', '2025-04-13 21:11:18'),
(150, 47, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 21:13:30', NULL),
(151, 47, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-13 21:15:48', NULL),
(152, 47, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 21:16:06', NULL),
(153, 47, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 21:16:24', NULL),
(154, 47, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 21:17:20', NULL),
(155, 47, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 21:17:45', NULL),
(156, 47, 'Debit Alert', 'Your account has been debited with $32', 'Unread', '2025-04-13 21:22:08', NULL),
(157, 34, 'Credit Alert', 'Your account has been credited with $32', 'Unread', '2025-04-13 21:22:08', NULL),
(158, 47, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-13 21:23:35', NULL),
(159, 47, 'Debit Alert', 'Your account has been debited with $600', 'Unread', '2025-04-13 21:24:41', NULL),
(160, 35, 'Credit Alert', 'Your account has been credited with $600', 'Unread', '2025-04-13 21:24:41', NULL),
(161, 47, 'Support Email Sent', 'A support email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-13 21:40:28', NULL),
(162, 47, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-13 21:41:59', NULL),
(163, 47, 'Password Update', 'Password update was successful', 'Unread', '2025-04-13 21:45:35', NULL),
(164, 47, 'Password Update', 'Password update was successful', 'Unread', '2025-04-13 21:45:46', NULL),
(165, 47, 'Pin Update', 'Transaction Pin update was successful', 'Unread', '2025-04-13 21:46:58', NULL),
(166, 47, 'Debit Alert', 'Your account has been debited with $121', 'Unread', '2025-04-13 21:54:34', NULL),
(167, 47, 'Credit Alert', 'Your account has been credited with $121', 'Unread', '2025-04-13 21:54:34', NULL),
(168, 47, 'Your OTP for Fund Transfer', 'An OTP has been sent to udoigweuchechukwu@gmail.com. Please check your inbox.', 'Unread', '2025-04-13 21:58:08', NULL),
(169, 47, 'Debit Alert', 'Your account has been debited with $56', 'Unread', '2025-04-13 21:58:30', NULL),
(170, 35, 'Credit Alert', 'Your account has been credited with $56', 'Unread', '2025-04-13 21:58:30', NULL),
(171, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-13 22:04:51', NULL),
(172, 48, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-13 22:08:07', NULL),
(173, 22, 'Account Creation Notification', 'An account creation notification email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-13 22:08:07', '2025-04-13 22:08:16'),
(174, 48, 'Account Update', 'Your account was updated successfully', 'Unread', '2025-04-13 22:09:25', NULL),
(175, 22, 'Account Update', 'User updated successfully', 'Unread', '2025-04-13 22:09:25', NULL),
(176, 48, 'Account Update', 'Your account was updated successfully', 'Unread', '2025-04-13 22:09:50', NULL),
(177, 22, 'Account Update', 'User updated successfully', 'Unread', '2025-04-13 22:09:50', NULL),
(178, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-13 22:10:48', NULL),
(179, 30, 'Login Successful', 'Welcome onboard Yaz Sullivan', 'Unread', '2025-04-13 22:20:04', NULL),
(180, 43, 'Login Successful', 'Welcome onboard Laura Hills', 'Unread', '2025-04-13 22:25:07', NULL),
(181, 43, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-13 22:25:53', NULL),
(182, 43, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-13 22:27:49', NULL),
(183, 43, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-13 22:28:22', NULL),
(184, 43, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 22:37:00', NULL),
(185, 43, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 22:37:16', NULL),
(186, 43, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 22:40:34', NULL),
(187, 43, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-13 22:40:44', NULL),
(188, 22, 'Login Successful', 'Welcome onboard Mike Mayers', 'Unread', '2025-04-16 18:51:06', NULL),
(189, 43, 'Login Successful', 'Welcome onboard Laura Hills', 'Unread', '2025-04-16 18:51:31', NULL),
(190, 43, 'Password Update', 'Password update was successful', 'Unread', '2025-04-16 19:21:56', NULL),
(191, 43, 'Pin Update', 'Transaction Pin update was successful', 'Unread', '2025-04-16 19:28:08', NULL),
(192, 43, 'Debit Alert', 'Your account has been debited with $100', 'Unread', '2025-04-16 19:49:30', NULL),
(193, 43, 'Credit Alert', 'Your account has been credited with $100', 'Unread', '2025-04-16 19:49:30', NULL),
(194, 23, 'Login Successful', 'Welcome onboard John Woo', 'Unread', '2025-04-16 19:51:16', NULL),
(195, 23, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-16 19:52:27', NULL),
(196, 23, 'Password Update', 'Password update was successful', 'Unread', '2025-04-16 19:55:28', NULL),
(197, 49, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-16 20:05:02', NULL),
(198, 49, 'Budget Categories', 'Setup budget categories to track spending', 'Unread', '2025-04-16 20:05:02', NULL),
(199, 50, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-16 20:08:47', NULL),
(200, 50, 'Budget Categories', 'Setup budget categories to track spending', 'Unread', '2025-04-16 20:08:47', NULL),
(201, 51, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-16 20:12:36', NULL),
(202, 51, 'Budget Categories', 'Setup budget categories to track spending', 'Unread', '2025-04-16 20:12:36', NULL),
(203, 52, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-16 20:19:12', NULL),
(204, 52, 'Budget Categories', 'Setup budget categories to track spending', 'Unread', '2025-04-16 20:19:12', NULL),
(205, 52, 'Login Successful', 'Welcome onboard Love Iheanacho', 'Unread', '2025-04-16 20:21:26', NULL),
(206, 52, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-16 20:29:13', NULL),
(207, 52, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-16 20:30:32', NULL),
(208, 44, 'Login Successful', 'Welcome onboard Harission Ford', 'Unread', '2025-04-16 21:38:26', NULL),
(209, 53, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-16 21:49:24', NULL),
(210, 53, 'Budget Categories', 'Setup budget categories to track spending', 'Unread', '2025-04-16 21:49:24', NULL),
(211, 53, 'Login Successful', 'Welcome onboard Lawrence Fishborne', 'Unread', '2025-04-16 21:49:41', NULL);

--
-- Triggers `notifications`
--
DELIMITER $$
CREATE TRIGGER `before_update_notifications` BEFORE UPDATE ON `notifications` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `notifications_view`
-- (See below for the actual view)
--
CREATE TABLE `notifications_view` (
`notification_id` int(11)
,`user_id` int(11)
,`title` varchar(255)
,`message` text
,`notification_status` enum('Read','Unread')
,`created_at` timestamp
,`updated_at` timestamp
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
,`role` enum('Admin','Account Officer','Customer')
);

-- --------------------------------------------------------

--
-- Table structure for table `otp`
--

CREATE TABLE `otp` (
  `otp_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `otp` int(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `otp_view`
-- (See below for the actual view)
--
CREATE TABLE `otp_view` (
`otp_id` int(11)
,`account_id` int(11)
,`otp` int(6)
,`created_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `safe_lock`
--

CREATE TABLE `safe_lock` (
  `safe_lock_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `balance` float NOT NULL,
  `lock_start_time` timestamp NULL DEFAULT current_timestamp(),
  `lock_end_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `safe_lock`
--

INSERT INTO `safe_lock` (`safe_lock_id`, `account_id`, `balance`, `lock_start_time`, `lock_end_time`, `created_at`, `updated_at`) VALUES
(3, 1000000029, 0, '2025-03-31 22:12:00', '2025-04-29 22:42:00', '2025-03-31 22:12:43', '2025-04-16 21:12:28'),
(6, 1000000032, 0, '2025-04-01 05:47:17', '2025-04-23 06:17:17', '2025-04-01 05:47:17', '2025-04-16 21:12:07'),
(7, 1000000033, 336.95, '2025-04-01 21:48:00', '2025-04-29 21:48:00', '2025-04-01 21:48:19', '2025-04-05 23:12:16'),
(8, 1000000034, 100, '2025-04-11 20:42:00', '2025-04-21 20:42:00', '2025-04-11 20:42:53', '2025-04-16 21:11:51'),
(9, 1000000035, 32, '2025-04-11 21:26:00', '2025-04-30 21:26:00', '2025-04-11 21:26:35', '2025-04-11 21:42:23'),
(10, 1000000036, 0, '2025-04-13 20:48:00', '2025-05-13 20:48:00', '2025-04-13 20:48:27', '2025-04-13 20:50:59'),
(11, 1000000037, 0, '2025-04-13 21:05:26', '2025-04-26 21:35:26', '2025-04-13 21:05:26', '2025-04-16 21:11:29'),
(12, 1000000038, 62.2, '2025-04-13 21:08:00', '2025-05-29 21:08:00', '2025-04-13 21:08:44', '2025-04-13 21:58:24'),
(13, 1000000039, 0, '2025-04-16 20:04:59', '2025-04-16 20:34:59', '2025-04-16 20:04:59', NULL),
(14, 1000000040, 0, '2025-04-16 20:08:00', '2025-04-16 20:38:42', '2025-04-16 20:08:42', '2025-04-16 21:10:35'),
(15, 1000000041, 0, '2025-04-16 20:12:00', '2025-04-30 20:42:00', '2025-04-16 20:12:32', '2025-04-16 21:10:25'),
(16, 1000000042, 0, '2025-04-16 20:19:00', '2025-05-16 20:19:00', '2025-04-16 20:19:08', '2025-04-16 20:29:13'),
(17, 1000000043, 0, '2025-04-16 21:49:00', '2025-05-16 21:49:00', '2025-04-16 21:49:21', NULL);

--
-- Triggers `safe_lock`
--
DELIMITER $$
CREATE TRIGGER `before_update_safe_lock` BEFORE UPDATE ON `safe_lock` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `safe_lock_view`
-- (See below for the actual view)
--
CREATE TABLE `safe_lock_view` (
`safe_lock_id` int(11)
,`account_id` int(11)
,`balance` float
,`lock_start_time` timestamp
,`lock_end_time` timestamp
,`created_at` timestamp
,`updated_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
,`account_type` enum('Savings','Current','Fixed Deposit')
,`pin` int(6)
);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `sender_account_id` int(11) DEFAULT NULL,
  `budget_category_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Credit','Debit') NOT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `transaction_fee` float NOT NULL DEFAULT 0,
  `balance_after_transaction` float NOT NULL,
  `transaction_budget_status` enum('Exceeds Budget','Within Budget') DEFAULT NULL,
  `transaction_description` text NOT NULL,
  `transaction_source` enum('Main Account','Safe Lock') NOT NULL DEFAULT 'Main Account',
  `transaction_destination` enum('Main Account','Safe Lock') NOT NULL DEFAULT 'Main Account',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `account_id`, `sender_account_id`, `budget_category_id`, `transaction_type`, `amount`, `transaction_fee`, `balance_after_transaction`, `transaction_budget_status`, `transaction_description`, `transaction_source`, `transaction_destination`, `created_at`) VALUES
(20, 1000000033, NULL, 20, 'Debit', 141.5, 0, 1358.5, 'Within Budget', 'Funds Transfer', 'Main Account', 'Main Account', '2025-04-05 19:06:02'),
(21, 1000000032, 1000000033, NULL, 'Credit', 141.5, 0, 1641.5, NULL, 'Funds Received', 'Main Account', 'Main Account', '2025-04-05 19:06:02'),
(22, 1000000033, NULL, 20, 'Debit', 150, 0, 1208.5, 'Within Budget', 'Funds Transfer', 'Main Account', 'Main Account', '2025-04-05 19:07:36'),
(23, 1000000032, 1000000033, NULL, 'Credit', 150, 0, 1791.5, NULL, 'Funds Received', 'Main Account', 'Main Account', '2025-04-05 19:07:36'),
(24, 1000000033, NULL, 20, 'Debit', 142.56, 0, 1065.94, 'Within Budget', 'Funds Transfer', 'Main Account', 'Main Account', '2025-04-05 19:12:55'),
(25, 1000000032, 1000000033, NULL, 'Credit', 142.56, 0, 1934.06, NULL, 'Funds Received', 'Main Account', 'Main Account', '2025-04-05 19:12:55'),
(26, 1000000033, NULL, 20, 'Debit', 141.76, 0, 924.18, 'Exceeds Budget', 'Funds Transfer', 'Main Account', 'Main Account', '2025-04-05 19:49:10'),
(27, 1000000032, 1000000033, NULL, 'Credit', 141.76, 0, 2075.82, NULL, 'Funds Received', 'Main Account', 'Main Account', '2025-04-05 19:49:10'),
(28, 1000000033, NULL, 18, 'Debit', 150, 0, 774.18, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-05 19:51:41'),
(29, 1000000033, 1000000033, NULL, 'Credit', 150, 0, 250, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-05 19:51:41'),
(30, 1000000033, NULL, 18, 'Debit', 130, 0, 644.18, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-05 19:53:08'),
(31, 1000000033, 1000000033, NULL, 'Credit', 130, 0, 380, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-05 19:53:08'),
(32, 1000000033, NULL, 18, 'Debit', 115, 0, 529.18, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-05 22:34:41'),
(33, 1000000033, NULL, NULL, 'Credit', 115, 0, 495, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-05 22:34:41'),
(34, 1000000033, NULL, 18, 'Debit', 112, 0, 417.18, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-05 22:35:47'),
(35, 1000000033, NULL, NULL, 'Credit', 112, 0, 607, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-05 22:35:47'),
(36, 1000000033, NULL, 18, 'Debit', 12, 0, 405.18, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-05 22:39:58'),
(37, 1000000033, 1000000033, NULL, 'Credit', 12, 0, 619, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-05 22:39:58'),
(38, 1000000033, NULL, 18, 'Debit', 13, 0, 392.18, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-05 22:43:49'),
(39, 1000000033, 1000000033, NULL, 'Credit', 13, 0, 632, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-05 22:43:49'),
(40, 1000000033, NULL, 19, 'Debit', 34, 0, 358.18, 'Within Budget', 'Funds Transfer', 'Main Account', 'Main Account', '2025-04-05 22:49:13'),
(41, 1000000032, 1000000033, NULL, 'Credit', 34, 0, 2109.82, NULL, 'Funds Received', 'Main Account', 'Main Account', '2025-04-05 22:49:13'),
(42, 1000000033, NULL, 20, 'Debit', 13, 0.65, 358.18, 'Exceeds Budget', 'Funds Transfer', 'Safe Lock', 'Main Account', '2025-04-05 22:51:48'),
(43, 1000000032, 1000000033, NULL, 'Credit', 13, 0, 2122.82, NULL, 'Funds Received', 'Safe Lock', 'Safe Lock', '2025-04-05 22:51:48'),
(44, 1000000033, NULL, 19, 'Debit', 34, 1.7, 582.65, 'Within Budget', 'Funds Transfer', 'Safe Lock', 'Main Account', '2025-04-05 23:06:29'),
(45, 1000000032, 1000000033, NULL, 'Credit', 34, 0, 2156.82, NULL, 'Funds Received', 'Safe Lock', 'Safe Lock', '2025-04-05 23:06:29'),
(46, 1000000033, NULL, 19, 'Debit', 234, 11.7, 336.95, 'Exceeds Budget', 'Funds Transfer', 'Safe Lock', 'Main Account', '2025-04-05 23:12:16'),
(47, 1000000032, 1000000033, NULL, 'Credit', 234, 0, 2390.82, NULL, 'Funds Received', 'Safe Lock', 'Safe Lock', '2025-04-05 23:12:16'),
(48, 1000000035, NULL, 22, 'Debit', 32, 0, 1468, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-11 21:42:23'),
(49, 1000000035, 1000000035, NULL, 'Credit', 32, 0, 32, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-11 21:42:23'),
(50, 1000000038, NULL, 26, 'Debit', 32, 0, 1468, 'Within Budget', 'Funds Transfer', 'Main Account', 'Main Account', '2025-04-13 21:22:02'),
(51, 1000000032, 1000000038, NULL, 'Credit', 32, 0, 2422.82, NULL, 'Funds Received', 'Main Account', 'Main Account', '2025-04-13 21:22:02'),
(52, 1000000038, NULL, 26, 'Debit', 600, 0, 868, 'Exceeds Budget', 'Funds Transfer', 'Main Account', 'Main Account', '2025-04-13 21:24:36'),
(53, 1000000033, 1000000038, NULL, 'Credit', 600, 0, 958.18, NULL, 'Funds Received', 'Main Account', 'Main Account', '2025-04-13 21:24:36'),
(54, 1000000038, NULL, 25, 'Debit', 121, 0, 747, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-13 21:54:26'),
(55, 1000000038, 1000000038, NULL, 'Credit', 121, 0, 121, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-13 21:54:26'),
(56, 1000000038, NULL, 26, 'Debit', 56, 2.8, 62.2, 'Exceeds Budget', 'Funds Transfer', 'Safe Lock', 'Main Account', '2025-04-13 21:58:24'),
(57, 1000000033, 1000000038, NULL, 'Credit', 56, 0, 1014.18, NULL, 'Funds Received', 'Safe Lock', 'Safe Lock', '2025-04-13 21:58:24'),
(58, 1000000034, NULL, 21, 'Debit', 100, 0, 1400, 'Within Budget', 'Funds Transfer', 'Main Account', 'Safe Lock', '2025-04-16 19:49:19'),
(59, 1000000034, 1000000034, NULL, 'Credit', 100, 0, 100, NULL, 'Funds Received', 'Main Account', 'Safe Lock', '2025-04-16 19:49:19');

-- --------------------------------------------------------

--
-- Stand-in structure for view `transactions_view`
-- (See below for the actual view)
--
CREATE TABLE `transactions_view` (
`transaction_id` int(11)
,`account_id` int(11)
,`sender_account_id` int(11)
,`budget_category_id` int(11)
,`transaction_type` enum('Credit','Debit')
,`amount` float
,`transaction_fee` float
,`balance_after_transaction` float
,`transaction_budget_status` enum('Exceeds Budget','Within Budget')
,`transaction_description` text
,`transaction_source` enum('Main Account','Safe Lock')
,`transaction_destination` enum('Main Account','Safe Lock')
,`created_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
,`account_type` enum('Savings','Current','Fixed Deposit')
,`category_name` varchar(255)
,`color_code` varchar(255)
,`sender_first_name` varchar(255)
,`sender_last_name` varchar(255)
,`sender_phone` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Others') NOT NULL,
  `dob` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `identification` enum('Drivers License','SSN') NOT NULL,
  `identification_number` varchar(255) NOT NULL,
  `role` enum('Admin','Account Officer','Customer') NOT NULL DEFAULT 'Customer',
  `password` varchar(255) NOT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `hash_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_seen` timestamp NULL DEFAULT NULL,
  `account_status` enum('Active','Inactive') NOT NULL DEFAULT 'Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `gender`, `dob`, `address`, `phone`, `email`, `identification`, `identification_number`, `role`, `password`, `hash`, `hash_time`, `last_seen`, `account_status`, `created_at`, `updated_at`) VALUES
(22, 'Mike', 'Mayers', 'Male', '1972-03-22', 'Texas ', '09089098789', 'admin@finhive.com', 'Drivers License', '1234554232', 'Admin', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', 'd707329bece455a462b58ce00d1194c9', '2025-04-16 18:51:06', '2025-04-16 18:51:06', 'Active', '2025-03-17 23:22:15', '2025-04-16 18:51:06'),
(23, 'John', 'Woo', 'Male', '1969-03-02', 'Texas', '09089098788', 'john@gmail.com', 'Drivers License', '12321232', 'Account Officer', '$2y$10$PZWcytRX5E/F6/zkOrwnS.oRa1tP.1g031mOCSHf.AShCmqZfQ2nW', '647bba344396e7c8170902bcf2e15551', '2025-04-16 19:55:28', '2025-04-16 19:51:16', 'Active', '2025-03-19 00:15:14', '2025-04-16 19:55:28'),
(24, 'Wilson', 'Gihon', 'Male', '1981-09-10', 'State House, Winsconsin', '+1-2910-10291', 'gihon@finhive.com', 'Drivers License', '1110192019', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:25:27', NULL, 'Active', '2025-03-30 00:41:44', '2025-04-11 20:25:27'),
(26, 'James', 'Earl', 'Male', '2001-03-30', 'Washington DC', '+1-902-01992', 'james-earl@gmail.com', 'SSN', '238923-298923', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:25:31', NULL, 'Active', '2025-03-30 01:34:07', '2025-04-11 20:25:31'),
(27, 'China', 'Aduino', 'Female', '2001-03-30', 'Texas Houston', '090817291', 'china@finhive.com', 'Drivers License', '12112121', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:25:34', NULL, 'Active', '2025-03-30 01:35:59', '2025-04-11 20:25:34'),
(28, 'Jazmine', 'Sullivan', 'Female', '2001-03-30', 'Texas Houston', '+1-291-29182', 'jazmine@finhive.com', 'Drivers License', '128981729', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:25:38', NULL, 'Active', '2025-03-30 01:38:01', '2025-04-11 20:25:38'),
(30, 'Yaz', 'Sullivan', 'Female', '2001-02-03', 'Winsconsin', '+1-2910-1029', 'yaz@gmail.com', 'Drivers License', '1213233', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-13 22:20:04', '2025-04-13 22:20:04', 'Active', '2025-03-30 22:08:43', '2025-04-13 22:20:04'),
(31, 'Uchechukwu', 'Udo', 'Male', '2001-03-30', 'Winsconsin', '08065198300', 'udoigweuchechukwu1@gmail.com', 'Drivers License', '112232323', 'Customer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', '8f7d807e1f53eff5f9efbe5cb81090fb', '2025-04-11 20:25:49', '2025-04-01 03:53:54', 'Active', '2025-03-31 22:12:43', '2025-04-11 20:25:49'),
(34, 'Ikem', 'Abia', 'Male', '1998-07-10', 'New York', '0909817281', 'udoigweuchechukwu2@gmail.com', 'Drivers License', '332242', 'Customer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', '1fc214004c9481e4c8073e85323bfd4b', '2025-04-11 20:25:53', '2025-04-01 21:32:53', 'Active', '2025-04-01 05:47:17', '2025-04-11 20:25:53'),
(35, 'Tolu', 'Ayo', 'Male', '2001-07-05', 'Baltimore, Maryland', '0908789185', 'udoigweuchechukwu3@gmail.com', 'Drivers License', '76837364', 'Customer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', '34ed066df378efacc9b924ec161e7639', '2025-04-11 21:16:45', '2025-04-11 21:16:45', 'Active', '2025-04-01 21:48:19', '2025-04-11 21:16:45'),
(38, 'James', 'Mark', 'Male', '1999-02-10', 'Main Street, New York', '+1 2348 1291 01', 'udoigweuchechukwu4@gmail.com', 'Drivers License', '12345531', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:26:00', NULL, 'Active', '2025-04-10 19:25:49', '2025-04-11 20:26:00'),
(39, 'Jazmine', 'Yaz', 'Female', '2000-02-10', 'Main Street Kansas City', '+1 2345 1121', 'yazmine@gmail.com', 'Drivers License', '1234554422', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:26:03', NULL, 'Active', '2025-04-10 19:57:50', '2025-04-11 20:26:03'),
(40, 'Melvine', 'Heinz', 'Female', '1999-09-10', 'Main Street Califonia', '+1291 19281 821', 'heinz@gmail.com', 'SSN', '123212321', 'Account Officer', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:26:06', '2025-04-11 19:47:49', 'Active', '2025-04-10 20:05:18', '2025-04-11 20:26:06'),
(42, 'Tom', 'Halland', 'Male', '2003-03-10', 'Main Street North Jakorta', '+1 909 8019 192', 'udoigweuchechukwu5@gmail.com', 'Drivers License', '12343212', 'Admin', '$2y$10$mGQNHY/ZVeHmemgScw8NXOwcKb/5hA7hoB9qI/FDRPeGXX3vgc3aC', NULL, '2025-04-11 20:41:24', NULL, 'Inactive', '2025-04-10 20:16:54', '2025-04-11 20:41:24'),
(43, 'Laura', 'Hills', 'Female', '1984-02-11', 'Portland Oregon', '+1 9829 8192 81', 'udoigweuchechukwu6@gmail.com', 'Drivers License', '1234321', 'Customer', '$2y$10$NsaTYnhXgiUmF0QX2THrEuQNqRsABO0L.8z9ADX4lX...8nkzDxjq', '6ea2ef7311b482724a9b7b0bc0dd85c6', '2025-04-16 19:21:56', '2025-04-16 18:51:31', 'Active', '2025-04-11 20:42:53', '2025-04-16 19:21:56'),
(44, 'Harission', 'Ford', 'Male', '1985-12-31', 'New Hamshire', '+1 234 019 1029', 'udoigweuchechukwu7@gmail.com', 'Drivers License', '178123212', 'Customer', '$2y$10$OcQtDArRXkdS3D7J/PxaG.pSiD1IohzrqOEA15P182PK2fp7BKnje', '84117275be999ff55a987b9381e01f96', '2025-04-16 21:38:26', '2025-04-16 21:38:26', 'Active', '2025-04-11 21:26:35', '2025-04-16 21:38:26'),
(45, 'James', 'Earl', 'Male', '1986-06-11', 'Montreal', '+1 2343 1221 12', 'udoigweuchechukwu8@gmail.com', 'Drivers License', '123456', 'Customer', '$2y$10$52TFK7yCbKGjtG432dp0le1r6SgEcPOJT4LHOS4NISZHXrzbN.N1O', '65658fde58ab3c2b6e5132a39fae7cb9', '2025-04-13 21:03:58', '2025-04-13 20:49:02', 'Active', '2025-04-13 20:48:27', '2025-04-13 21:03:58'),
(46, 'Michael', 'Jackson', 'Male', '2000-04-03', 'Neverland Ranch', '+1 1233 1212 121', 'udoigweuchechukwu9@gmail.com', 'Drivers License', '1234344', 'Customer', '$2y$10$5wDHgpgMIoM3bg5MZu41H..yvZkp0yX6LPK/CIQqO0EbYIw8s7pK2', '061412e4a03c02f9902576ec55ebbe77', '2025-04-13 21:07:38', '2025-04-13 21:06:10', 'Active', '2025-04-13 21:05:26', '2025-04-13 21:07:38'),
(47, 'Helena', 'Kane', 'Female', '2002-02-13', 'Massachusets', '+ 1232 12123', 'udoigweuchechukwu9@gmail.com', 'Drivers License', '1234', 'Customer', '$2y$10$ATNHH..LvMZeuGj9FCfJZug8FdC.h4n31GBCaLiozC/DTyse3jA5y', '182be0c5cdcd5072bb1864cdee4d3d6e', '2025-04-13 22:06:45', '2025-04-13 21:09:34', 'Active', '2025-04-13 21:08:44', '2025-04-13 22:06:45'),
(48, 'John', 'Cena', 'Male', '1963-03-13', 'New Mexico', '+1 1231 1211', 'udoigweuchechukwu10@gmail.com', 'Drivers License', '143235234525', 'Account Officer', '$2y$10$ATNHH..LvMZeuGj9FCfJZug8FdC.h4n31GBCaLiozC/DTyse3jA5y', NULL, '2025-04-16 20:02:14', NULL, 'Inactive', '2025-04-13 22:08:04', '2025-04-16 20:02:14'),
(49, 'Malika', 'Lisa', 'Female', '1991-03-16', 'North Carolina', '+1 1239 9182 91', 'udoigweuchechukwu11@gmail.com', 'Drivers License', '224353', 'Customer', '$2y$10$2XOXpuXbTXHAi209QIpV8eW58MzJIyr7YcdNAI.fUxRnYrJkEQdoy', 'b5dc4e5d9b495d0196f61d45b26ef33e', '2025-04-16 20:07:05', NULL, 'Inactive', '2025-04-16 20:04:59', '2025-04-16 20:07:05'),
(50, 'Lax', 'Onomeous', 'Male', '2000-03-16', 'New York', '+1 2928 918291', 'udoigweuchechukwu12@gmail.com', 'Drivers License', 'e33434', 'Customer', '$2y$10$KojCb7Nt7S8qgt2H.jbFr.aiUQlEQKCyH7yV0fm.vdvoopQPntDuG', '30ef30b64204a3088a26bc2e6ecf7602', '2025-04-16 20:10:44', NULL, 'Inactive', '2025-04-16 20:08:42', '2025-04-16 20:10:44'),
(51, 'Iron', 'Man', 'Male', '1990-02-16', 'New York', '+1 324 1213 2424', 'udoigweuchechukwu13@gmail.com', 'Drivers License', '123431212', 'Customer', '$2y$10$nkHthAHB3WUaQfhKP9gJPOtjgo7Jfs21yR2.QukSvmC3VHAjRLPEu', 'a666587afda6e89aec274a3657558a27', '2025-04-16 20:16:56', NULL, 'Inactive', '2025-04-16 20:12:32', '2025-04-16 20:16:56'),
(52, 'Love', 'Iheanacho', 'Male', '2002-02-16', 'South Carolina', '+1 2343 1234 12', 'udoigweuchechukwu14@gmail.com', 'Drivers License', '23224242', 'Customer', '$2y$10$.w1Ss9nyiRTWYHHcNNFXd.yA2MwIoL.hs/dcA1tuzNjk70H9wneK2', '1be3bc32e6564055d5ca3e5a354acbef', '2025-04-16 21:48:18', '2025-04-16 20:21:26', 'Active', '2025-04-16 20:19:08', '2025-04-16 21:48:18'),
(53, 'Lawrence', 'Fishborne', 'Male', '1991-01-16', 'China Town', '+1 4829 182 712', 'udoigweuchechukwu@gmail.com', 'Drivers License', '123456786', 'Customer', '$2y$10$2sn/sV1N/VG1QtCTEjFHv.0wdBWFG5vQug71vYCZ5cwHz7ql6gDJy', '0f49c89d1e7298bb9930789c8ed59d48', '2025-04-16 21:49:41', '2025-04-16 21:49:41', 'Active', '2025-04-16 21:49:21', '2025-04-16 21:49:41');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `age_check_before_customer_insert` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'Customer' AND TIMESTAMPDIFF(YEAR, NEW.dob, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Customers must be at least 18 years old';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `age_check_before_customer_update` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'Customer' AND TIMESTAMPDIFF(YEAR, NEW.dob, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Customers must be at least 18 years old';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_users` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `create_account_and_safe_lock_after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    DECLARE new_account_id INT;

    -- Only process if the new user is a customer
    IF NEW.role = 'Customer' THEN
        -- Call the stored procedure to create an account and get the account_id
        CALL newAccount(NEW.user_id, new_account_id);

        -- Insert a record into the safe_lock table using the new account_id
        IF new_account_id IS NOT NULL THEN
            INSERT INTO safe_lock (account_id, balance, lock_start_time, lock_end_time)
            VALUES (new_account_id, 0.0, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:00'), DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 30 DAY), '%Y-%m-%d %H:%i:00'));
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure for view `account_view`
--
DROP TABLE IF EXISTS `account_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `account_view`  AS SELECT `a`.`account_id` AS `account_id`, `a`.`user_id` AS `user_id`, `a`.`account_officer_id` AS `account_officer_id`, `a`.`account_type` AS `account_type`, `a`.`pin` AS `pin`, `a`.`balance` AS `balance`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`dob` AS `dob`, `b`.`email` AS `email`, `b`.`password` AS `password`, `b`.`gender` AS `gender`, `b`.`identification` AS `identification`, `b`.`identification_number` AS `identification_number`, `b`.`phone` AS `phone`, `b`.`role` AS `role`, `b`.`last_seen` AS `last_seen`, `b`.`created_at` AS `joined_at`, `b`.`account_status` AS `account_status`, `c`.`first_name` AS `account_officer_first_name`, `c`.`last_name` AS `account_officer_last_name`, `c`.`phone` AS `account_officer_phone`, `c`.`email` AS `account_officer_email` FROM ((`account` `a` left join `users` `b` on(`a`.`user_id` = `b`.`user_id`)) left join `users` `c` on(`a`.`account_officer_id` = `c`.`user_id`)) WHERE `b`.`role` = 'Customer' ;

-- --------------------------------------------------------

--
-- Structure for view `budget_categories_view`
--
DROP TABLE IF EXISTS `budget_categories_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `budget_categories_view`  AS SELECT `a`.`category_id` AS `category_id`, `a`.`account_id` AS `account_id`, `a`.`category_name` AS `category_name`, `a`.`category_description` AS `category_description`, `a`.`budget_limit` AS `budget_limit`, `a`.`budget_limit_start_time` AS `budget_limit_start_time`, `a`.`budget_limit_end_time` AS `budget_limit_end_time`, `a`.`color_code` AS `color_code`, `a`.`budget_category_status` AS `budget_category_status`, `a`.`created_at` AS `created_at`, `a`.`edited_at` AS `edited_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone` FROM (`budget_categories` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `cards_view`
--
DROP TABLE IF EXISTS `cards_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cards_view`  AS SELECT `a`.`card_id` AS `card_id`, `a`.`account_id` AS `account_id`, `a`.`card_number` AS `card_number`, `a`.`expiry_date` AS `expiry_date`, `a`.`card_type` AS `card_type`, `a`.`issue_date` AS `issue_date`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone` FROM (`cards` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `notifications_view`
--
DROP TABLE IF EXISTS `notifications_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notifications_view`  AS SELECT `a`.`notification_id` AS `notification_id`, `a`.`user_id` AS `user_id`, `a`.`title` AS `title`, `a`.`message` AS `message`, `a`.`notification_status` AS `notification_status`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone`, `b`.`role` AS `role` FROM (`notifications` `a` left join `users` `b` on(`a`.`user_id` = `b`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `otp_view`
--
DROP TABLE IF EXISTS `otp_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `otp_view`  AS SELECT `a`.`otp_id` AS `otp_id`, `a`.`account_id` AS `account_id`, `a`.`otp` AS `otp`, `a`.`created_at` AS `created_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone` FROM (`otp` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `safe_lock_view`
--
DROP TABLE IF EXISTS `safe_lock_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `safe_lock_view`  AS SELECT `a`.`safe_lock_id` AS `safe_lock_id`, `a`.`account_id` AS `account_id`, `a`.`balance` AS `balance`, `a`.`lock_start_time` AS `lock_start_time`, `a`.`lock_end_time` AS `lock_end_time`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone`, `b`.`account_type` AS `account_type`, `b`.`pin` AS `pin` FROM (`safe_lock` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `transactions_view`
--
DROP TABLE IF EXISTS `transactions_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `transactions_view`  AS SELECT `a`.`transaction_id` AS `transaction_id`, `a`.`account_id` AS `account_id`, `a`.`sender_account_id` AS `sender_account_id`, `a`.`budget_category_id` AS `budget_category_id`, `a`.`transaction_type` AS `transaction_type`, `a`.`amount` AS `amount`, `a`.`transaction_fee` AS `transaction_fee`, `a`.`balance_after_transaction` AS `balance_after_transaction`, `a`.`transaction_budget_status` AS `transaction_budget_status`, `a`.`transaction_description` AS `transaction_description`, `a`.`transaction_source` AS `transaction_source`, `a`.`transaction_destination` AS `transaction_destination`, `a`.`created_at` AS `created_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone`, `b`.`account_type` AS `account_type`, `c`.`category_name` AS `category_name`, `c`.`color_code` AS `color_code`, `d`.`first_name` AS `sender_first_name`, `d`.`last_name` AS `sender_last_name`, `d`.`phone` AS `sender_phone` FROM (((`transactions` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) left join `budget_categories` `c` on(`a`.`budget_category_id` = `c`.`category_id`)) left join `account_view` `d` on(`a`.`sender_account_id` = `d`.`account_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `account_officer_id` (`account_officer_id`);

--
-- Indexes for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`card_id`),
  ADD UNIQUE KEY `card_number` (`card_number`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp`
--
ALTER TABLE `otp`
  ADD PRIMARY KEY (`otp_id`),
  ADD KEY `user_id` (`account_id`);

--
-- Indexes for table `safe_lock`
--
ALTER TABLE `safe_lock`
  ADD PRIMARY KEY (`safe_lock_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `budget_category_id` (`budget_category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_categories`
--
ALTER TABLE `budget_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cards`
--
ALTER TABLE `cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp`
--
ALTER TABLE `otp`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `safe_lock`
--
ALTER TABLE `safe_lock`
  MODIFY `safe_lock_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `account_ibfk_2` FOREIGN KEY (`account_officer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD CONSTRAINT `budget_categories_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `cards`
--
ALTER TABLE `cards`
  ADD CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `otp`
--
ALTER TABLE `otp`
  ADD CONSTRAINT `otp_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `safe_lock`
--
ALTER TABLE `safe_lock`
  ADD CONSTRAINT `safe_lock_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`budget_category_id`) REFERENCES `budget_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
